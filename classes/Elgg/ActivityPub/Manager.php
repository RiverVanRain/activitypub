<?php

namespace Elgg\ActivityPub;

use Elgg\ActivityPub\Entity\ActivityPubActivity;
use Elgg\ActivityPub\Enums\FederatedEntitySourcesEnum;
use Elgg\ActivityPub\Exceptions\MissingEntityException;
use Elgg\ActivityPub\Helpers\JsonLdHelper;
use Elgg\ActivityPub\Types\Actor\AbstractActorType;
use Elgg\ActivityPub\WebFinger\WebfingerService;
use Elgg\Exceptions\Http\BadRequestException;
use Elgg\Traits\Di\ServiceFacade;

class Manager
{
    use ServiceFacade;

    protected $webfingerService;

    public function __construct(
        WebfingerService $webfingerService,
    ) {
        $this->webfingerService = $webfingerService;
    }

    /**
     * Returns registered service name
     * @return string
     */
    public static function name()
    {
        return 'activityPubManager';
    }

    /**
     * Returns a Uri for an entity
     */
    public function getUriFromEntity(\ElggEntity $entity): string
    {
        if (!$entity) {
            return null;
        }
        /**
         * Attempt to get the uri from the remote
         */
        if (($entity instanceof \Elgg\ActivityPub\Entity\FederatedUser || $entity instanceof \Elgg\ActivityPub\Entity\FederatedGroup || $entity instanceof \Elgg\ActivityPub\Entity\FederatedObject) && (string) $entity->source === FederatedEntitySourcesEnum::ACTIVITY_PUB) {
            $uri = (string) $entity->canonical_url;

            if ($uri) {
                return $uri;
            }
        }

        /**
         * If not found in the table we construct the uri manually
         */
        return elgg_generate_url("view:activitypub:{$entity->getType()}", [
            'guid' => (int) $entity->guid
        ]);
    }

    /**
     * Returns an actor uri from a username (either local or remote).
     * It will first attempt to find a user by their username
     * If that is not found we will fetch the actor from their webfinger resource
     */
    public function getUriFromUsername(string $username, bool $revalidateWebfinger = false): ?string
    {
        $username = ltrim(strtolower($username), '@');

        $user = elgg_get_user_by_username($username);

        if ($user instanceof \ElggUser && !((string) $user->source === FederatedEntitySourcesEnum::ACTIVITY_PUB && $revalidateWebfinger)) {
            return $this->getUriFromEntity($user);
        }

        if (strpos($username, '@') === false) {
            return null;
        }

        // The user doesn't exist on Elgg, so try to find from their webfinger
        try {
            $json = $this->webfingerService->get('acct:' . $username);

            foreach ($json['links'] as $link) {
                if ($link['rel'] === 'self') {
                    return $link['href'];
                }
            }
        } catch (\Exception $e) {
        }

        return null;
    }

    /**
     * Returns Elgg entity from a uri
     * Supports returning entities from both remote and local uris
     * @return ElggEntity
     */
    public function getEntityFromUri(string $uri): ?\ElggEntity
    {
        // Does the $uri start with our local domain
        if ($this->isLocalUri($uri)) {
            return $this->getEntityFromLocalUri($uri);
        }

        //FederatedEntity
        $entities = elgg_get_entities([
            'types' => ['user', 'group', 'object'],
            'subtypes' => 'federated',
            'metadata_name_value_pairs' => [
                [
                    'name' => 'canonical_url',
                    'value' => $uri,
                ],
            ],
            'limit' => 1,
        ]);

        // Do we have a copy of this locally?
        foreach ($entities as $entity) {
            return $entity;
        }

        return null;
    }

    /**
     * Returns Elgg entity from its ActivityPub id. This function should be used
     * when the $objectUri is a local one.
     */
    public function getEntityFromLocalUri(string $objectUri): ?\ElggEntity
    {
        if (!$this->isLocalUri($objectUri)) {
            return null;
        }

        $pathUri = str_replace($this->getBaseUrl(), '', $objectUri);

        $pathParts = explode('/', $pathUri);

        if (count($pathParts) === 2) {
            if ($pathParts[0] === 'users') {
                // This will be users/GUID
                $entityGuid = (int) $pathParts[1];

                $user = get_user($entityGuid);

                if (!$user) {
                    return null;
                }

                return $user;
            } elseif ($pathParts[0] === 'groups') {
                // This will be groups/GUID
                $entityGuid = (int) $pathParts[1];

                // access bypass for getting invisible group
                $group = elgg_call(ELGG_IGNORE_ACCESS, function () use ($entityGuid) {
                    return get_entity($entityGuid);
                });

                if (!$group instanceof \ElggGroup) {
                    return null;
                }

                return $group;
            } elseif ($pathParts[0] === 'activity') {
                // This will be an activity
                $entityGuid = (int) $pathParts[1];

                $activity = elgg_call(ELGG_IGNORE_ACCESS, function () use ($entityGuid) {
                    return get_entity($entityGuid);
                });

                if (!$activity instanceof ActivityPubActivity) {
                    return null;
                }

                return $activity;
            } elseif ($pathParts[0] === 'object') {
                // This will be an object
                $entityGuid = (int) $pathParts[1];

                $object = elgg_call(ELGG_IGNORE_ACCESS, function () use ($entityGuid) {
                    return get_entity($entityGuid);
                });

                if (!$object instanceof \ElggObject) {
                    return null;
                }

                return $object;
            }
        }

        return null;
    }

    /**
     * Returns the base url that we will use for all of our Ids
     */
    public function getBaseUrl(): string
    {
        return elgg_get_site_url() . 'activitypub/';
    }

    /**
     * Returns true if the activity pub uri matches the Elgg app site url
     */
    public function isLocalUri($uri): bool
    {
        return strpos($uri, $this->getBaseUrl(), 0) === 0;
    }
}
