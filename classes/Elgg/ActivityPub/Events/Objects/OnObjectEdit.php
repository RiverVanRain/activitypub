<?php

/**
 * ActivityPub
 * @author Nikolai Shcherbin
 * @license GNU Affero General Public License version 3
 * @copyright (c) Nikolai Shcherbin 2024
 * @link https://wzm.me
**/

namespace Elgg\ActivityPub\Events\Objects;

use Elgg\ActivityPub\Entity\ActivityPubActivity;

class OnObjectEdit
{
    public function __invoke(\Elgg\Event $event)
    {
        $entity = $event->getObject();

        if (!$entity instanceof \ElggObject) {
            return;
        }

        $svc = elgg()->activityPubUtility;
        $subtypes = $svc->getDynamicSubTypes();

        if (!(bool) elgg_get_plugin_setting("can_activitypub:object:$entity->subtype", 'activitypub') && !in_array($entity->subtype, $subtypes)) {
            return;
        }

        if (empty((int) $entity->activity_reference) || (int) $entity->activity_reference === 0) {
            return;
        }

        if ($entity->published_status === 'draft' || $entity->status === 'draft') {
            return;
        }

        $user = $entity->getOwnerEntity();

        if (!$user instanceof \ElggUser) {
            return;
        }

        if (!$user->isAdmin() && !(bool) elgg()->activityPubUtility->isEnabledUser($user)) {
            return;
        }

        $group = $entity->getContainerEntity();

        if ($group instanceof \ElggGroup && !(bool) elgg()->activityPubUtility->isEnabledGroup($group)) {
            return;
        }

        return elgg_call(ELGG_IGNORE_ACCESS, function () use ($entity) {
            $activity = get_entity((int) $entity->activity_reference);

            if ($activity instanceof ActivityPubActivity && (bool) $activity->isProcessed()) {
                $activity->setMetadata('updated', date('c', (int) $entity->time_updated));
            }
        });
    }
}
