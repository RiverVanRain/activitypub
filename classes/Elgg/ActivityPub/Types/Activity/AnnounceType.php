<?php
namespace Elgg\ActivityPub\Types\Activity;

use Elgg\ActivityPub\Attributes\ExportProperty;
use Elgg\ActivityPub\Types\Core\ActivityType;

/**
 * https://www.w3.org/TR/activitystreams-vocabulary/#dfn-announce
 */
class AnnounceType extends ActivityType
{
    #[ExportProperty]
    protected string $type = 'Announce';

}
