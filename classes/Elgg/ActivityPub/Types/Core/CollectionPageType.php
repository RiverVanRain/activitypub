<?php
namespace Elgg\ActivityPub\Types\Core;

use Elgg\ActivityPub\Attributes\ExportProperty;

class CollectionPageType extends CollectionType {
    use CollectionPageTypeTrait;

    #[ExportProperty]
    protected string $type = 'CollectionPage';
}
