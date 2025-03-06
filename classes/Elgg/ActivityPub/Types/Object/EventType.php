<?php
namespace Elgg\ActivityPub\Types\Object;

use Elgg\ActivityPub\Attributes\ExportProperty;
use Elgg\ActivityPub\Types\Core\ObjectType;

class EventType extends ObjectType {
    #[ExportProperty]
    protected string $type = 'Event';
}
