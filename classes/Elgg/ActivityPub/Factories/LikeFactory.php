<?php

namespace Elgg\ActivityPub\Factories;

use Elgg\ActivityPub\Entity\ActivityPubActivity;
use Elgg\ActivityPub\Manager;
use Elgg\ActivityPub\Types\Activity\LikeType;
use Elgg\ActivityPub\Types\Core\OrderedCollectionPageType;
use Elgg\Traits\Di\ServiceFacade;

class LikeFactory
{
    use ServiceFacade;

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
    public static function name()
    {
        return 'activityPubLikeFactory';
    }

    /**
     * {@inheritdoc}
     */
    public function __get($name)
    {
        return $this->$name;
    }

    /**
     * Constructs an outbox for a user
     */
    public function build(string $uri, \ElggUser $user): OrderedCollectionPageType
    {
        $orderedCollection = new OrderedCollectionPageType();
        $orderedCollection->setId($uri);

        $orderedCollection->setPartOf($uri . 'outbox');

        $items = [];

        $limit = (int) max(get_input('limit', max(25, _elgg_services()->config->default_limit)), 0);

        $options = [
            'types' => 'object',
            'subtypes' => ActivityPubActivity::SUBTYPE,
            'owner_guid' => (int)  $user->guid,
            'access_id' => ACCESS_PUBLIC,
            'metadata_name_value_pairs' => [
                [
                    'name' => 'status',
                    'value' => 1,
                ],
                [
                    'name' => 'actor',
                    'value' => elgg_generate_url('view:activitypub:user', [
                        'guid' => (int) $user->guid,
                    ]),
                ],
                [
                    'name' => 'collection',
                    'value' => ActivityPubActivity::LIKED,
                ],
                [
                    'name' => 'activity_type',
                    'value' => 'Like',
                ],
            ],
            'limit' => $limit,
            'offset' => $offset,
        ];

        $activities = elgg_call(ELGG_IGNORE_ACCESS, function () use ($options) {
            return elgg_get_entities($options);
        });

        foreach ($activities as $activity) {
            $object = elgg()->activityPubObjectFactory->fromEntity($activity->getEntity());

            $actor = elgg()->activityPubObjectFactory->fromEntity($user);

            $item = new LikeType();
            $item->id = "$actor->id/$object->id/like";
            $item->actor = $actor;
            $item->object = $object;

            $items[] = $item;
        }

        $orderedCollection->setOrderedItems($items);

        return $orderedCollection;
    }
}
