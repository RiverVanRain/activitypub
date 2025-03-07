<?php

/**
 * ActivityPub
 * @author Nikolai Shcherbin
 * @license GNU Affero General Public License version 3
 * @copyright (c) Nikolai Shcherbin 2024
 * @link https://wzm.me
**/

namespace Elgg\ActivityPub\Events\Users;

use Elgg\ActivityPub\Entity\ActivityPubActivity;

class OnDeleteFriend
{
    /**
     * Listen to the delete the friend relationship
     *
     * @param \Elgg\Event $event 'delete', 'relationship'
     *
     * @return void
     */
    public function __invoke(\Elgg\Event $event)
    {

        $relationship = $event->getObject();
        if (!$relationship instanceof \ElggRelationship || $relationship->relationship !== 'friend') {
            return;
        }

        $actor = get_user((int) $relationship->guid_one);
        if (!$actor instanceof \ElggUser) {
            return;
        }

        $remote_friend = get_user((int) $relationship->guid_two);
        if (!$remote_friend instanceof \Elgg\ActivityPub\Entity\FederatedUser) {
            return;
        }

        // prevent deadloops
        elgg_unregister_event_handler($event->getName(), $event->getType(), __METHOD__);

        $svc = elgg()->activityPubUtility;

        $activity = new ActivityPubActivity();
        $activity->owner_guid = (int) $actor->guid;
        $activity->access_id = ACCESS_PUBLIC;
        $activity->setMetadata('collection', ActivityPubActivity::OUTBOX);
        $activity->setMetadata('activity_type', 'Undo');
        $activity->setMetadata('actor', elgg()->activityPubUtility->getActivityPubID($actor));
        $activity->setMetadata('activity_object', (string) $remote_friend->canonical_url);
        $activity->setMetadata('processed', 0);
        $activity->setMetadata('status', 0);

        if ($activity->canBeQueued()) {
            $activity->setMetadata('queued', 1);
        }

        $follows = elgg_call(ELGG_IGNORE_ACCESS, function () use ($actor, $remote_friend) {
            return elgg_get_entities([
                'type' => 'object',
                'subtype' => ActivityPubActivity::SUBTYPE,
                'metadata_name_value_pairs' => [
                    [
                        'name' => 'activity_object',
                        'value' => (string) $remote_friend->canonical_url,
                    ],
                    [
                        'name' => 'actor',
                        'value' => elgg()->activityPubUtility->getActivityPubID($actor),
                    ],
                    [
                        'name' => 'activity_type',
                        'value' => 'Follow',
                    ],
                ],
            ]);
        });

        if (!empty($follows)) {
            foreach ($follows as $f) {
                $activity->setMetadata('external_id', (string) $f->getURL());
            }
        }

        if (!$activity->save()) {
            elgg_log(elgg_echo('activitypub:outbox:undo:error', [(int) $actor->guid, (int) $remote_friend->guid]), \Psr\Log\LogLevel::ERROR);
            return false;
        }

        $remote_friend->removeRelationship((int) $actor->guid, 'remote_friend');

        // re-register listener
        elgg_register_event_handler($event->getName(), $event->getType(), __METHOD__);
    }
}
