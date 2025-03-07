<?php

namespace Elgg\ActivityPub\Permissions;

use Elgg\Groups\ToolContainerLogicCheck;

/**
 * Prevent blogs from being created if the group tool option is disabled
 */
class GroupToolContainerLogicCheck extends ToolContainerLogicCheck
{
    /**
     * {@inheritDoc}
     */
    public function getContentType(): string
    {
        return 'object';
    }

    /**
     * {@inheritDoc}
     */
    public function getContentSubtype(): string
    {
        return \Elgg\ActivityPub\Entity\ActivityPubActivity::SUBTYPE;
    }

    /**
     * {@inheritDoc}
     */
    public function getToolName(): string
    {
        return 'activitypub';
    }
}
