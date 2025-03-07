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

class OnObjectPublish
{
    public function __invoke(\Elgg\Event $event)
    {
        $entity = $event->getObject();

        if (!$entity instanceof \ElggObject) {
            return;
        }

        if ((bool) $entity->scheduling_prevent && $entity->published_status !== 'published') {
            return;
        }

        $subtypes = elgg()->activityPubUtility->getDynamicSubTypes();

        if (!(bool) elgg_get_plugin_setting("can_activitypub:object:$entity->subtype", 'activitypub') && !in_array($entity->subtype, $subtypes)) {
            return;
        }

        $activity = get_entity((int) $entity->activity_reference);
        if (!$activity instanceof ActivityPubActivity) {
            return;
        }

        $activity->setMetadata('status', 1);

        if (!$activity->save()) {
            if ((bool) elgg_get_plugin_setting('log_general_inbox_error', 'activitypub')) {
                $this->log(elgg_echo('activitypub:activitypub_activity:save:error', ['Event: OnObjectPublish, Object GUID: ' . (int) $entity->guid]));
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
