<?php

namespace Elgg\ActivityPub\Types\Object;

use Elgg\ActivityPub\Attributes\ExportProperty;
use Elgg\ActivityPub\Types\Actor\PersonType;
use Elgg\ActivityPub\Types\Core\ObjectType;

class PageType extends ObjectType
{
    #[ExportProperty]
    protected string $type = 'Page';

    public ?PersonType $actor = null;

    /**
     * Non-standard quote post field
     * TODO: Do proper schema validation for this property
     */
    public ?string $quoteUri;
}
