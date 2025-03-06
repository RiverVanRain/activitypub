<?php
namespace Elgg\ActivityPub\Types\Actor;

use Elgg\ActivityPub\Attributes\ExportProperty;

/**
 * https://www.w3.org/TR/activitystreams-vocabulary/#dfn-application
 */
class ApplicationType extends AbstractActorType {
    #[ExportProperty]
    protected string $type = 'Application';
}
