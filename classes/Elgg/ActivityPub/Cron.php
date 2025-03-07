<?php

/**
 * ActivityPub
 * @author Nikolai Shcherbin
 * @license GNU Affero General Public License version 3
 * @copyright (c) Nikolai Shcherbin 2024
 * @link https://wzm.me
**/

namespace Elgg\ActivityPub;

use Elgg\ActivityPub\Entity\ActivityPubActivity;

class Cron
{
    public static function processOutbox(\Elgg\Event $event)
    {
        if (!(bool) elgg_get_plugin_setting('process_outbox_handler', 'activitypub')) {
            return;
        }

        elgg_call(ELGG_IGNORE_ACCESS, function () {
            elgg()->activityPubProcessClient->prepareOutboxQueue();
        });
    }

    public static function processInbox(\Elgg\Event $event)
    {
        if (!(bool) elgg_get_plugin_setting('process_inbox_handler', 'activitypub')) {
            return;
        }

        elgg_call(ELGG_IGNORE_ACCESS, function () {
            elgg()->activityPubProcessClient->handleInboxQueue();
        });
    }

    public static function removeOutbox(\Elgg\Event $event)
    {
        if (!(bool) elgg_get_plugin_setting('remove_outbox_activities', 'activitypub')) {
            return;
        }

        $time = (int) $event->getParam('time', time());

        elgg_call(ELGG_IGNORE_ACCESS, function () use ($time) {
            $activities = elgg_get_entities([
                'type' => 'object',
                'subtype' => ActivityPubActivity::SUBTYPE,
                'metadata_name_value_pairs' => [
                    [
                        'name' => 'collection',
                        'value' => ActivityPubActivity::OUTBOX,
                    ],
                    [
                        'name' => 'status',
                        'value' => 0,
                    ],
                    [
                        'name' => 'processed',
                        'value' => 0,
                    ],
                    [
                        'name' => 'queued',
                        'value' => 1,
                    ],
                    [
                        'name' => 'time_created',
                        'value' => $time + 60, // + 1 minute
                        'operand' => '<',
                    ],
                ],
                'limit' => false,
                'batch' => true,
                'batch_size' => 50,
                'batch_inc_offset' => false
            ]);

            foreach ($activities as $activity) {
                $activity->delete();
            }
        });
    }

    public static function removeInbox(\Elgg\Event $event)
    {
        if (!(bool) elgg_get_plugin_setting('remove_inbox_activities', 'activitypub')) {
            return;
        }

        $time = (int) $event->getParam('time', time());

        elgg_call(ELGG_IGNORE_ACCESS, function () use ($time) {
            $activities = elgg_get_entities([
                'type' => 'object',
                'subtype' => ActivityPubActivity::SUBTYPE,
                'metadata_name_value_pairs' => [
                    [
                        'name' => 'collection',
                        'value' => ActivityPubActivity::INBOX,
                    ],
                    [
                        'name' => 'status',
                        'value' => 0,
                    ],
                    [
                        'name' => 'processed',
                        'value' => 0,
                    ],
                    [
                        'name' => 'queued',
                        'value' => 1,
                    ],
                    [
                        'name' => 'time_created',
                        'value' => $time + 60, // + 1 minute
                        'operand' => '<',
                    ],
                ],
                'limit' => false,
                'batch' => true,
                'batch_size' => 50,
                'batch_inc_offset' => false
            ]);

            foreach ($activities as $activity) {
                $activity->delete();
            }
        });
    }

    public static function importFollowers(\Elgg\Event $event)
    {
        if (!(bool) elgg_get_plugin_setting('process_inbox_handler', 'activitypub')) {
            return;
        }

        elgg_call(ELGG_IGNORE_ACCESS, function () {
            $followers = elgg_get_entities([
                'types' => 'object',
                'subtypes' => ActivityPubActivity::SUBTYPE,
                'metadata_name_value_pairs' => [
                    [
                        'name' => 'status',
                        'value' => 1,
                    ],
                    [
                        'name' => 'processed',
                        'value' => 1,
                    ],
                    [
                        'name' => 'collection',
                        'value' => ActivityPubActivity::INBOX,
                    ],
                    [
                        'name' => 'activity_type',
                        'value' => 'Follow',
                    ],
                ],
                'limit' => false,
                'batch' => true,
                'batch_size' => 50,
                'batch_inc_offset' => false,
            ]);

            foreach ($followers as $follower) {
                elgg()->activityPubReader->import($follower->getActor());
            }
        });
    }
}
