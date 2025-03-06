<?php
namespace Elgg\ActivityPub\Types\Link;

use Elgg\ActivityPub\Attributes\ExportProperty;
use Elgg\ActivityPub\Types\Core\LinkType;

class MentionType extends LinkType {
    #[ExportProperty]
    protected string $type = 'Mention';
}
