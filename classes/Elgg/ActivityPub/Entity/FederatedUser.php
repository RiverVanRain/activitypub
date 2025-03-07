<?php

/**
 * Entities that will be federated should implement the following functions
 */

namespace Elgg\ActivityPub\Entity;

class FederatedUser extends \ElggUser implements FederatedEntityInterface
{
    const SUBTYPE = 'federated';

    /**
     * {@inheritdoc}
     */
    protected function initializeAttributes()
    {
        parent::initializeAttributes();
        $this->attributes['subtype'] = self::SUBTYPE;
    }

    /**
     * Returns whether the actor is remote friend or not.
     *
     * @return bool
     */
    public function isRemote(\ElggEntity $requestor): bool
    {
        return $this->hasRelationship((int) $requestor->guid, 'remote_friend');
    }
}
