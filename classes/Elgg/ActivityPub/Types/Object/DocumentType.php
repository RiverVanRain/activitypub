<?php

namespace Elgg\ActivityPub\Types\Object;

use Elgg\ActivityPub\Attributes\ExportProperty;
use Elgg\ActivityPub\Types\Core\ObjectType;

class DocumentType extends ObjectType
{
    #[ExportProperty]
    protected string $type = 'Document';
}
