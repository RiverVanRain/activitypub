<?php

/**
 * ActivityPub
 * @author Nikolai Shcherbin
 * @license GNU Affero General Public License version 3
 * @copyright (c) Nikolai Shcherbin 2024
 * @link https://wzm.me
**/

namespace Elgg\ActivityPub\Events\Users;

class OnUserDelete
{
    public function __invoke(\Elgg\Event $event)
    {

        $entity = $event->getObject();
        if (!$entity instanceof \ElggUser) {
            return;
        }

        if (!(bool) $entity->activitypub_actor) {
            return;
        }

        return elgg()->activityPubSignature->deleteKeys((string) $entity->username);
    }
}
