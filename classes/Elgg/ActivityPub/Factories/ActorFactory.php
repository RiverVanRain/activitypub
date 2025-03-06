<?php

namespace Elgg\ActivityPub\Factories;

use GuzzleHttp\Exception\ConnectException;
use Elgg\ActivityPub\Entity\ActivityPubActivity;
use Elgg\ActivityPub\Exceptions\NotImplementedException;
use Elgg\ActivityPub\Manager;
use Elgg\ActivityPub\Types\Actor\AbstractActorType;
use Elgg\ActivityPub\Types\Actor\ApplicationType;
use Elgg\ActivityPub\Types\Actor\GroupType;
use Elgg\ActivityPub\Types\Actor\OrganizationType;
use Elgg\ActivityPub\Types\Actor\PersonType;
use Elgg\ActivityPub\Types\Actor\PublicKeyType;
use Elgg\ActivityPub\Types\Actor\ServiceType;
use Elgg\ActivityPub\Types\Core\SourceType;
use Elgg\ActivityPub\Types\Object\ImageType;
use Elgg\Exceptions\Http\EntityNotFoundException;
use Elgg\Exceptions\Http\PageNotFoundException;
use Elgg\Traits\Di\ServiceFacade;

class ActorFactory {
    use ServiceFacade;

    public const ACTOR_TYPES = [
        'Person' => PersonType::class,
        'Application' => ApplicationType::class,
        'Group' => GroupType::class,
        'Organization' => OrganizationType::class,
        'Service' => ServiceType::class,
    ];

    public const ELGG_APPLICATION_ACTOR_GUID = 1;

	protected $manager;

    public function __construct(
        Manager $manager,
    ) {
        $this->manager = $manager;
    }

    /**
	 * Returns registered service name
	 * @return string
	 */
	public static function name() {
		return 'activityPubActorFactory';
	}

    /**
	 * {@inheritdoc}
	 */
	public function __get($name) {
		return $this->$name;
	}

    /**
     * Builds an actor from their webfinger resource
     */
    public function fromWebfinger(string $username): AbstractActorType {
        $uri = $this->manager->getUriFromUsername($username, revalidateWebfinger: true);

        if ($uri) {
            return $this->fromUri($uri);
        }

        throw new PageNotFoundException();
    }

    /**
     * Builds Actor from a uri.
     */
    public function fromUri(string $uri): AbstractActorType {
        if ($this->manager->isLocalUri($uri)) {
            $entity = $this->manager->getEntityFromUri($uri);
            if (!$entity) {
                throw new PageNotFoundException();
            }
            return $this->fromEntity($entity);
        }

        try {
			$response = elgg()->activityPubClient->request('GET', $uri);
			
            $json = json_decode($response->getBody()->getContents(), true);
        } catch (ConnectException $e) {
            throw new EntityNotFoundException("Could not connect to $uri");
        }

        if (!is_array($json)) {
            throw new PageNotFoundException();
        }

        return $this->fromJson($json);
    }

    /**
     * Builds Actor from a local entity.
     */
    public function fromEntity(\ElggGroup|\ElggUser $entity): AbstractActorType {
        // Build the json array from the entity
        if (!$entity instanceof \ElggEntity) {
            throw new NotImplementedException();
        }
		
		if ($entity instanceof \ElggUser && (!(bool) elgg_get_plugin_setting('enable_activitypub', 'activitypub') || !(bool) $entity->getPluginSetting('activitypub', 'enable_activitypub') || !(bool) $entity->activitypub_actor)) {
			throw new PageNotFoundException();
		}
		
		if ($entity instanceof \ElggGroup && (!elgg_is_active_plugin('groups') || !(bool) elgg_get_plugin_setting('enable_group', 'activitypub') || !(bool) $entity->activitypub_enable || !(bool) $entity->activitypub_actor)) {
			throw new PageNotFoundException();
		}

        /**
         * If we are building a remote user, then use their uri
         */
        if ($uri = $this->manager->getUriFromEntity($entity)) {
            if (!$this->manager->isLocalUri($uri)) {
                return $this->fromUri($uri);
            }
        }
		
		$activityPubSignature = elgg()->activityPubSignature;
		$activityPubUtility = elgg()->activityPubUtility;
		
		// Actor type
		$actorType = $activityPubUtility->getActivityPubActorType($entity);
		
		// Preferred username
		if ($entity instanceof \ElggUser) {
			$preferredUsername = (string) $entity->username;
		} else if ($entity instanceof \ElggGroup) {
			$preferredUsername = strstr((string) $entity->activitypub_name, '@', true);
		}
		
		// Manually approves followers
		if ($entity instanceof \ElggUser) {
			$manuallyApprovesFollowers = (bool) elgg_get_plugin_setting('friend_request', 'friends');
		} else if ($entity instanceof \ElggGroup) {
			$manuallyApprovesFollowers = (bool) $entity->isPublicMembership();
		}

		$data = [
			'id' => $activityPubUtility->getActivityPubID($entity),
			'type' => $actorType,
			'name' => (string) $entity->getDisplayName(),
			'preferredUsername' => $preferredUsername,
			'url' => (string) $entity->getURL(),
			'inbox' => (string) elgg_generate_url("view:activitypub:{$entity->getType()}:inbox", [
				'guid' => (int) $entity->guid,
			]),
			'outbox' => (string) elgg_generate_url("view:activitypub:{$entity->getType()}:outbox", [
				'guid' => (int) $entity->guid,
			]),
			'following' => (string) elgg_generate_url("view:activitypub:{$entity->getType()}:following", [
				'guid' => (int) $entity->guid,
			]),
			'followers' => (string) elgg_generate_url("view:activitypub:{$entity->getType()}:followers", [
				'guid' => (int) $entity->guid,
			]),
			'liked' => (string) elgg_generate_url("view:activitypub:{$entity->getType()}:liked", [
				'guid' => (int) $entity->guid,
			]),
			'manuallyApprovesFollowers' => $manuallyApprovesFollowers,
			'discoverable' => ($entity instanceof \ElggGroup) ? (bool) $entity->enable_discoverable : (bool) $entity->getPluginSetting('activitypub', 'enable_discoverable'),
			'indexable' => _elgg_services()->config->walled_garden ? false : true,
			'published' => date('c', (int) $entity->time_created),
			'updated' => date('c', (int) $entity->time_updated),
			'webfinger' => (string) $preferredUsername . '@' . (string) elgg_get_site_entity()->getDomain(),
			'attributionDomains' => [
				(string) elgg_get_site_entity()->getDomain()
			], 
			'publicKey' => [
				'id' => $activityPubUtility->getActivityPubID($entity) . '#main-key',
				'owner' => $activityPubUtility->getActivityPubID($entity),
				'publicKeyPem' => $activityPubSignature->getPublicKey($preferredUsername),
			],
			'endpoints' => [
                'sharedInbox' => (string) elgg_generate_url('view:activitypub:inbox'),
            ],
		];
		
		if ($entity instanceof \ElggUser && (bool) $entity->isBanned()) {
			$data['suspended'] = true;
		}

		if ($image_url = $activityPubUtility->getActivityPubActorImage($entity)) {
			$image = [
				'type' => 'Image',
				'name' => (string) $entity->getDisplayName(),
				'url' => $image_url,
			];
			$data['icon'] = $image;
		}

		if ($image_url = $activityPubUtility->getActivityPubActorImage($entity, 'cover')) {
			$image = [
				'type' => 'Image',
				'name' => (string) $entity->getDisplayName(),
				'url' => $image_url,
			];
			$data['image'] = $image;
		}
		
		if ($image_url = $activityPubUtility->getActivityPubActorImage($entity, 'header')) {
			$image = [
				'type' => 'Image',
				'name' => (string) $entity->getDisplayName(),
				'url' => $image_url,
			];
			$data['image'] = $image;
		}
		
		if (!empty((string) $entity->description)) {
			$options = [
				'parse_emails' => true,
				'parse_hashtags' => true,
				'parse_urls' => true,
				'parse_usernames' => true,
				'parse_groups' => true,
				'parse_mentions' => true,
				'oembed' => false,
				'sanitize' => true,
				'autop' => true,
			];
			
			$data['content'] = _elgg_services()->html_formatter->formatBlock((string) $entity->description, $options);
		}
		
		$summary = !empty((string) $entity->description) ? elgg_substr(trim(elgg_strip_tags((string) $entity->description)), 0, 500) : elgg_substr((string) $entity->briefdescription, 0, 500);
		
		if (!empty($summary)) {
			$data['summary'] = trim($summary);
			
			$data['source'] = [
				'content' => trim($summary),
				'mediaType' => 'text/markdown',
			];
			
			$data['_misskey_summary'] = trim($summary);
		}
		
		// Profile data
		$profileData = $activityPubUtility->getActorProfileData($entity);
		
		if (!empty($profileData)) {
			$data['attachment'] = $profileData;
		}

        return $this->fromJson($data);
    }

    /**
     * Pass through an array of data
     */
    public function fromJson(array $json): AbstractActorType {
        return $this->build($json);
    }

    /**
     * Builds the ActorType from the provided data
     */
    protected function build(array $json): AbstractActorType {
		$actor = match ($json['type']) {
			'Person' => new PersonType(),
			'Application' => new ApplicationType(),
			'Group' => new GroupType(),
			'Organization' => new OrganizationType(),
			'Service' => new ServiceType(),
			default => throw new NotImplementedException()
		};

		// Must
		if (!isset($json['id']) || !isset($json['inbox']) || !isset($json['outbox'])) {
			throw new \Exception('Required fields are missing');
		}

		$actor->id = $json['id'];
		$actor->inbox = $json['inbox'];
		$actor->outbox = $json['outbox'];

		// May
		if (isset($json['name'])) {
			$actor->name = $json['name'];
		}
		
		if (isset($json['preferredUsername'])) {
			$actor->preferredUsername = $json['preferredUsername'];
		}
		
		if (isset($json['webfinger'])) {
			$actor->webfinger = $json['webfinger'];
		}
		
		if (isset($json['url'])) {
			$actor->url = $json['url'];
		}
		
		if (isset($json['publicKey']) && is_array($json['publicKey'])) {
			$actor->publicKey = new PublicKeyType(
				id: $json['publicKey']['id'],
				owner: $json['publicKey']['owner'],
				publicKeyPem: $json['publicKey']['publicKeyPem'],
			);
		}

		if (isset($json['icon']) && is_array($json['icon'])) {
			$icon = new ImageType();
			if (isset($json['icon']['mediaType'])) {
				$icon->mediaType = $json['icon']['mediaType'];
			}
			$icon->url = $json['icon']['url'] ?? '';
			$icon->name = $json['icon']['name'] ?? '';
			$actor->icon = $icon;
		}

		if (isset($json['image']) && is_array($json['image'])) {
			$image = new ImageType();
			if (isset($json['image']['mediaType'])) {
				$image->mediaType = $json['image']['mediaType'];
			}
			$image->url = $json['image']['url'] ?? '';
			$image->name = $json['image']['name'] ?? '';
			$actor->image = $image;
		}

		if (isset($json['endpoints']) && is_array($json['endpoints'])) {
			$actor->endpoints = $json['endpoints'];
		}

		switch (get_class($actor)) {
			case GroupType::class:
			case PersonType::class:
				if (isset($json['following'])) {
					$actor->following = $json['following'];
				}
				if (isset($json['followers'])) {
					$actor->followers = $json['followers'];
				}
				if (isset($json['liked'])) {
					$actor->liked = $json['liked'];
				}
				if (isset($json['manuallyApprovesFollowers'])) {
					$actor->manuallyApprovesFollowers = $json['manuallyApprovesFollowers'];
				}
				if (isset($json['discoverable'])) {
					$actor->discoverable = $json['discoverable'];
				}
				if (isset($json['indexable'])) {
					$actor->indexable = $json['indexable'];
				}
				if (isset($json['published'])) {
					$actor->published = $json['published'];
				}
				if (isset($json['updated'])) {
					$actor->updated = $json['updated'];
				}
				if (isset($json['attributionDomains']) && is_array($json['attributionDomains'])) {
					$actor->attributionDomains = $json['attributionDomains'];
				}
				if (isset($json['suspended'])) {
					$actor->suspended = $json['suspended'];
				}
				if (isset($json['content'])) {
					$actor->content = $json['content'];
				}
				if (isset($json['summary'])) {
					$actor->summary = $json['summary'];
				}
				if (isset($json['source']) && is_array($json['source'])) {
					$actor->source = new SourceType();
					$actor->source->content = $json['source']['content'];
					$actor->source->mediaType = $json['source']['mediaType'];
				}
				if (isset($json['_misskey_summary'])) {
					$actor->_misskey_summary = $json['_misskey_summary'];
				}
				if (isset($json['attachment']) && is_array($json['attachment'])) {
					$actor->attachment = $json['attachment'];
				}
				
				break;
		}

		return $actor;
	}

    public function buildApplicationActor(): AbstractActorType {
        $actor = new ApplicationType();
        $actor->id = (string) elgg_generate_url('view:activitypub:application');
		$actor->name = (string) elgg_get_site_entity()->getDisplayName();
		$actor->preferredUsername = (string) elgg_get_site_entity()->getDomain();
		$actor->url = (string) elgg_get_site_url();
		$actor->inbox = (string) elgg_generate_url('view:activitypub:inbox');
		$actor->outbox = (string) elgg_generate_url('view:activitypub:outbox');
		$actor->manuallyApprovesFollowers = true;
		$actor->discoverable = false;
		$actor->indexable = false;
		$actor->published = date('c', (int) elgg_get_site_entity()->time_created);
		$actor->updated = date('c', (int) elgg_get_site_entity()->time_updated);
		$actor->webfinger = (string) elgg_get_site_entity()->getDomain() . '@' . (string) elgg_get_site_entity()->getDomain();
		
		$actor->publicKey = new PublicKeyType(
			id: (string) elgg_generate_url('view:activitypub:application') . '#main-key',
			owner: (string) elgg_generate_url('view:activitypub:application'),
			publicKeyPem: elgg()->activityPubSignature->getPublicKey((string) elgg_get_site_entity()->getDomain()),
		);
		
		if ($image_url = elgg()->activityPubUtility->getActivityPubActorImage(elgg_get_site_entity())) {
			$icon = new ImageType();
			$icon->url = $image_url;
			$icon->name = (string) elgg_get_site_entity()->getDisplayName();
			
			$actor->icon = $icon;
		}
		
		if (!empty((string) elgg_get_site_entity()->description)) {
			$actor->content = (string) elgg_get_site_entity()->description;
			
			$summary = elgg_substr(trim(elgg_strip_tags((string) elgg_get_site_entity()->description)), 0, 500);
			
			$actor->summary = trim($summary);

			$actor->source = new SourceType();
			$actor->source->content = trim($summary);
			$actor->source->mediaType = 'text/markdown';
			
			$actor->_misskey_summary = trim($summary);
		}
		
		return $actor;
    }
}
