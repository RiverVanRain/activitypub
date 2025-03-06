<?php
namespace Elgg\ActivityPub\Types\Object;

use Elgg\ActivityPub\Attributes\ExportProperty;

class VideoType extends DocumentType {
    #[ExportProperty]
    protected string $type = 'Video';
}
