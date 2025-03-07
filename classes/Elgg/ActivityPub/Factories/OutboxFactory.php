<?php

namespace Elgg\ActivityPub\Factories;

use Elgg\ActivityPub\Entity\ActivityPubActivity;
use Elgg\ActivityPub\Enums\ActivityFactoryOpEnum;
use Elgg\ActivityPub\Manager;
use Elgg\ActivityPub\Types\Core\OrderedCollectionPageType;
use Elgg\Traits\Di\ServiceFacade;

// WIP

class OutboxFactory
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
        return 'activityPubOutboxFactory';
    }

    /**
     * {@inheritdoc}
     */
    public function __get($name)
    {
        return $this->$name;
    }

    /**
     * Constructs an outbox for an entity
     */
    public function build(string $uri, \ElggEntity $user): OrderedCollectionPageType
    {
        $orderedCollection = new OrderedCollectionPageType();
        $orderedCollection->setId($uri);

        $orderedCollection->setPartOf($uri . 'outbox');

        $items = [];

        $limit = (int) max(get_input('limit', max(25, _elgg_services()->config->default_limit)), 0);

        $options = [
            'type' => 'object',
            'subtype' => ActivityPubActivity::SUBTYPE,
            'owner_guid' => (int) $user->guid,
            'access_id' => ACCESS_PUBLIC,
            'metadata_name_value_pairs' => [
                [
                    'name' => 'status',
                    'value' => 1,
                ],
                [
                    'name' => 'processed',
                    'value' => 1,
                ],
                [
                    'name' => 'collection',
                    'value' => ActivityPubActivity::OUTBOX,
                ],
                [
                    'name' => 'activity_type',
                    'value' => elgg()->activityPubUtility->getOutboxIgnoreTypes(),
                    'operand' => '!=',
                ],
            ],
            'limit' => $limit,
            //'offset' => $offset,
        ];

        $activities = elgg_call(ELGG_IGNORE_ACCESS, function () use ($options) {
            return elgg_get_entities($options);
        });

        foreach ($activities as $entity) {
            $items[] = elgg()->activityPubActivityFactory->fromEntity(ActivityFactoryOpEnum::CREATE, $entity, $user);
        }

        $orderedCollection->setOrderedItems($items);

        return $orderedCollection;
    }
}
