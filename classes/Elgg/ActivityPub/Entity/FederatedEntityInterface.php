<?php

/**
 * Entities that will be federated should implement the following functions
 */

namespace Elgg\ActivityPub\Entity;

interface FederatedEntityInterface
{
    /**
     * Returns whether the actor is remote or not.
     *
     * @return bool
     */
    public function isRemote(\ElggEntity $requestor);
}
