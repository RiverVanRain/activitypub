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

class OnRiverCreate
{
    public function __invoke(\Elgg\Event $event)
    {
        $object = $event->getParam('entity');
        $item = (array) $event->getParam('item');

        if (!$object instanceof \wZm\River\Entity\River || empty($item)) {
            return;
        }

        $object->external_id = (string) $item['id'];
        $object->canonical_url = (string) $item['canonical_url'];

        $object->status = 'published';
        $object->published_status = 'published';

        if (!empty($item['reply'])) {
            $object->reply_on = $item['reply'];
        }

        if (!empty($item['attachments'])) {
            $description = (string) $object->description;

            foreach ($item['attachments'] as $attachment) {
                if (!isset($attachment['type']) || !isset($attachment['url'])) {
                    continue;
                }
                if (!in_array($attachment['type'], ['Audio', 'Document', 'Image', 'Video'], true)) {
                    continue;
                }

                $title = (string) $attachment['url'];

                if (!empty($attachment['name'])) {
                    $title = (string) $attachment['name'];
                }

                $description .= elgg_view('activitypub/object/attachments', [
                    'attachments' => [
                        'type' => (string) $attachment['type'],
                        'mediaType' => !empty($attachment['mediaType']) ? (string) $attachment['mediaType'] : null,
                        'url' => (string) $attachment['url'],
                        'title' => $title,
                        'width' => !empty($attachment['width']) ? (string) $attachment['width'] : null,
                        'height' => !empty($attachment['height']) ? (string) $attachment['height'] : null,
                    ]
                ]);
            }

            $object->description = (string) $description;
        }

        if (!$object->save()) {
            if ((bool) elgg_get_plugin_setting('log_general_inbox_error', 'activitypub')) {
                $this->log(elgg_echo('activitypub:object:save:error', ['Event: OnRiverCreate, Object GUID: ' . (int) $object->guid]));
            }
            return false;
        }

        // create announce
        $group = $object->getContainerEntity();

        if ($group instanceof \ElggGroup) {
            $announce = new ActivityPubActivity();
            $announce->owner_guid = (int) $group->guid;
            $announce->access_id = (int) $object->access_id;
            $announce->setMetadata('collection', ActivityPubActivity::OUTBOX);
            $announce->setMetadata('activity_type', 'Announce');
            $announce->setMetadata('actor', (string) elgg()->activityPubUtility->getActivityPubID($group));
            $announce->setMetadata('activity_object', (string) $object->external_id);
            $announce->setMetadata('processed', 0);
            $announce->setMetadata('status', 0);

            if ($announce->canBeQueued()) {
                $announce->setMetadata('queued', 1);
            }

            if (!$announce->save()) {
                if ((bool) elgg_get_plugin_setting('log_general_inbox_error', 'activitypub')) {
                    $this->log(elgg_echo('activitypub:activitypub_activity:save:error', ['Event: OnGroupObjectCreate, Group GUID: ' . (int) $group->guid . ', Activity GUID: ' . (int) $activity->guid]));
                }
                return false;
            }
        }
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
