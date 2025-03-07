<?php

namespace Elgg\ActivityPub\Types\Object;

use Elgg\ActivityPub\Attributes\ExportProperty;
use Elgg\ActivityPub\Types\Actor\PersonType;
use Elgg\ActivityPub\Types\Core\ObjectType;

class ArticleType extends ObjectType
{
    #[ExportProperty]
    protected string $type = 'Article';

    public ?PersonType $actor = null;

    /**
     * Non-standard quote post field
     * TODO: Do proper schema validation for this property
     */
    public string $quoteUri;
}
