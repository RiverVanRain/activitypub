<?php

namespace Elgg\ActivityPub\Types\Core;

use Elgg\ActivityPub\Attributes\ExportProperty;

class OrderedCollectionPageType extends OrderedCollectionType
{
    use CollectionPageTypeTrait;

    #[ExportProperty]
    protected string $type = 'OrderedCollectionPage';
}
