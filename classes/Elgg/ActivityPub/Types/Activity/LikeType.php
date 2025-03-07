<?php

namespace Elgg\ActivityPub\Types\Activity;

use Elgg\ActivityPub\Attributes\ExportProperty;
use Elgg\ActivityPub\Types\Core\ActivityType;

/**
 * https://www.w3.org/TR/activitystreams-vocabulary/#dfn-like
 */
class LikeType extends ActivityType
{
    #[ExportProperty]
    protected string $type = 'Like';
}
