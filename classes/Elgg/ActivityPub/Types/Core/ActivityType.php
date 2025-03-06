<?php
namespace Elgg\ActivityPub\Types\Core;

use Elgg\ActivityPub\Attributes\ExportProperty;
use Elgg\ActivityPub\Helpers\JsonLdHelper;
use Elgg\ActivityPub\Types\Actor\AbstractActorType;

class ActivityType extends ObjectType {
    #[ExportProperty]
    protected string $type = 'Activity';

    #[ExportProperty]
    public AbstractActorType|string $actor;

    /**
     * @var ObjectType|string
     */
    #[ExportProperty]
    public ObjectType|string $object;

    public ?array $objects = null;

    public function export(array $extras = []): array {
        $exported = parent::export($extras);
        $exported['actor'] = JsonLdHelper::getValueOrId($this->actor);
        return $exported;
    }

}
