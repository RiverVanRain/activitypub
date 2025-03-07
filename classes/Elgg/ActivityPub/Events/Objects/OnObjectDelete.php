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

class OnObjectDelete
{
    public function __invoke(\Elgg\Event $event)
    {
        $entity = $event->getObject();

        if (!$entity instanceof \ElggObject || $entity instanceof ActivityPubActivity) {
            return;
        }

        if (empty((int) $entity->activity_reference) || (int) $entity->activity_reference === 0) {
            return;
        }

        $reference = get_entity((int) $entity->activity_reference);

        if ($reference instanceof ActivityPubActivity) {
            $activity = new ActivityPubActivity();
            $activity->owner_guid = (int) $reference->owner_guid;
            $activity->container_guid = (int) $reference->container_guid;
            $activity->access_id = (int) $reference->access_id;
            $activity->setMetadata('activity_type', 'Delete');
            $activity->setMetadata('actor', (string) $reference->getActor());
            $activity->setMetadata('activity_object', (string) $reference->getActivityObject());
            $activity->setMetadata('external_id', (string) $reference->getExternalId());
            //$activity->setMetadata('to', implode("\n", $reference->getTo()));
            $activity->setMetadata('collection', ActivityPubActivity::OUTBOX);
            $activity->setMetadata('processed', 0);
            $activity->setMetadata('status', 0);

            if ($activity->canBeQueued()) {
                $activity->setMetadata('queued', 1);
            }

            if (!$activity->save()) {
                elgg_log(elgg_echo('activitypub:events:activity:delete:error', [(int) $reference->guid]), \Psr\Log\LogLevel::ERROR);
                return false;
            }

            return $reference->delete();
        }

        return;
    }
}
