<?php

namespace Elgg\ActivityPub\Factories;

use ElggEntity;
use ElggUser;
use Elgg\ActivityPub\Entity\ActivityPubActivity;
use Elgg\ActivityPub\Enums\ActivityFactoryOpEnum;
use Elgg\ActivityPub\Exceptions\NotImplementedException;
use Elgg\ActivityPub\Helpers\JsonLdHelper;
use Elgg\ActivityPub\Manager;
use Elgg\ActivityPub\Types\Activity\AcceptType;
use Elgg\ActivityPub\Types\Activity\AnnounceType;
use Elgg\ActivityPub\Types\Activity\CreateType;
use Elgg\ActivityPub\Types\Activity\DeleteType;
use Elgg\ActivityPub\Types\Activity\FlagType;
use Elgg\ActivityPub\Types\Activity\FollowType;
use Elgg\ActivityPub\Types\Activity\LikeType;
use Elgg\ActivityPub\Types\Activity\UndoType;
use Elgg\ActivityPub\Types\Activity\UpdateType;
use Elgg\ActivityPub\Types\Actor\AbstractActorType;
use Elgg\ActivityPub\Types\Core\ActivityType;
use Elgg\Exceptions\Http\PageNotFoundException;
use Elgg\Traits\Di\ServiceFacade;

// WIP
class ActivityFactory
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
        return 'activityPubActivityFactory';
    }

    /**
     * {@inheritdoc}
     */
    public function __get($name)
    {
        return $this->$name;
    }

    public function fromEntity(
        ActivityFactoryOpEnum $op,
        ElggEntity $entity,
        ElggUser $actor
    ): ActivityType {
        $item = match ($op) {
            ActivityFactoryOpEnum::CREATE => new CreateType(),
            ActivityFactoryOpEnum::UPDATE => new UpdateType(),
            ActivityFactoryOpEnum::DELETE => new DeleteType(),
        };

        $object = elgg()->activityPubObjectFactory->fromEntity($entity);

        $activity_reference = (int) $object->activity_reference;
        $activity = elgg_call(ELGG_IGNORE_ACCESS, function () use ($activity_reference) {
            return get_entity($activity_reference);
        });

        $item->id = $this->manager->getUriFromEntity($entity) . '/activity';

        // If a remind has been deleted, do an Undo
        if ($op === ActivityFactoryOpEnum::DELETE && $isRemind) {
            $item = new UndoType();
            $item->id = annotationId();
            $object = $this->fromEntity(ActivityFactoryOpEnum::CREATE, $entity, $actor);
        }

        $item->actor = elgg()->activityPubActorFactory->fromEntity($actor);
        $item->object = $object;

        return $item;
    }

    public function fromJson(array $json, AbstractActorType|string $actor): ActivityType
    {
        $activity = match ($json['type']) {
            'Create' => new CreateType(),
            'Follow' => new FollowType(),
            'Like' => new LikeType(),
            'Flag' => new FlagType(),
            'Undo' => new UndoType(),
            'Accept' => new AcceptType(),
            'Announce' => new AnnounceType(),
            'Delete' => new DeleteType(),
            default => throw new NotImplementedException(),
        };

        // Must
        $activity->id = $json['id'];
        $activity->actor = $actor;

        $activity->object = match (get_class($activity)) {
            FollowType::class => elgg()->activityPubActorFactory->fromUri(JsonLdHelper::getValueOrId($json['object'])),
            UndoType::class => is_array($json['object']) ? $this->fromJson($json['object'], $actor) : throw new NotImplementedException(elgg_echo('UndoMustProvideJsonObject')),
            AcceptType::class => $this->fromJson($json['object'], $actor),
            LikeType::class => elgg()->activityPubObjectFactory->fromUri(JsonLdHelper::getValueOrId($json['object'])),
            FlagType::class => is_array($json['object']) ? "" : $json['object'],
            DeleteType::class => JsonLdHelper::getValueOrId($json['object']),
            AnnounceType::class => elgg()->activityPubObjectFactory->fromUri(JsonLdHelper::getValueOrId($json['object'])),
            default => elgg()->activityPubObjectFactory->fromJson($json['object']),
        };

        if (is_array($json['object'])) {
            $activity->objects = $json['object'];
        }

        return $activity;
    }
}
