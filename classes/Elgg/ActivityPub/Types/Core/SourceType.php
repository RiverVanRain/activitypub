<?php
namespace Elgg\ActivityPub\Types\Core;

use Elgg\ActivityPub\Attributes\ExportProperty;
use Elgg\ActivityPub\Types\AbstractType;

class SourceType extends AbstractType {
    protected string $type = 'Source';

    /**
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-content
     */
    #[ExportProperty]
    public string $content;

    /**
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-mediaType
     */
    #[ExportProperty]
    public string $mediaType;
}
