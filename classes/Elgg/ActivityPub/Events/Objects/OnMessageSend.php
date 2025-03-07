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
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class OnMessageSend
{
    public function __invoke(\Elgg\Event $event)
    {
        elgg_call(ELGG_IGNORE_ACCESS, function () use ($event) {
            $entity = $event->getObject();

            if (!$entity instanceof \wZm\Inbox\Message && !$entity instanceof \ElggMessage) {
                return;
            }

            $fromId = (int) $entity->fromId;

            $user = get_entity($fromId);

            if (!$user instanceof \ElggUser) {
                return;
            }

            if (!$user->isAdmin() && (!(bool) $user->getPluginSetting('activitypub', 'enable_activitypub') || !(bool) $user->activitypub_actor)) {
                return;
            }

            $toIds = $entity->toId;

            if (!is_array($toIds)) {
                $toIds = [$toIds];
            }

            $to = [];

            foreach ($toIds as $toId) {
                if ($toId === (int) $user->guid) {
                    continue;
                }

                $recipient = get_entity($toId);

                if ($recipient instanceof \Elgg\ActivityPub\Entity\FederatedUser) {
                    $to[] = (string) $recipient->canonical_url;
                } else {
                    $to[] = elgg()->activityPubUtility->getActivityPubID($recipient);
                }
            }

            $entity_url = elgg_generate_url('view:activitypub:object', [
                'guid' => (int) $entity->guid,
            ]);

            $activity = new ActivityPubActivity();
            $activity->owner_guid = (int) $user->guid;
            $activity->container_guid = (int) $user->guid;
            $activity->access_id = $entity->access_id;
            $activity->setMetadata('activity_type', 'Create');
            $activity->setMetadata('activity_object', $entity_url);
            $activity->setMetadata('collection', ActivityPubActivity::OUTBOX);
            $activity->setMetadata('actor', elgg()->activityPubUtility->getActivityPubID($user));
            $activity->setMetadata('entity_subtype', (string) $entity->subtype);
            $activity->setMetadata('entity_guid', (int) $entity->guid);
            $activity->setMetadata('to', implode("\n", $to));
            $activity->setMetadata('processed', 0);
            $activity->setMetadata('status', 0);

            if ($activity->canBeQueued()) {
                $activity->setMetadata('queued', 1);
            }

            if (!$activity->save()) {
                if ((bool) elgg_get_plugin_setting('log_general_inbox_error', 'activitypub')) {
                    $this->log(elgg_echo('activitypub:activitypub_activity:save:error', ['Event: OnObjectCreate, Object GUID: ' . (int) $entity->guid]));
                }
            }

            $entity->setMetadata('activity_reference', (int) $activity->guid);
        });
    }

    /** Logger */
    public function log($message = '')
    {
        $log_file = elgg_get_data_path() . 'activitypub/logs/log_general_inbox_error';

        $log = new Logger('ActivityPub');
        $log->pushHandler(new StreamHandler($log_file, Logger::WARNING));

        // add records to the log
        return $log->warning($message);
    }
}
