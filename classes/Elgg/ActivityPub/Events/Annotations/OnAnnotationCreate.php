<?php

/**
 * ActivityPub
 * @author Nikolai Shcherbin
 * @license GNU Affero General Public License version 3
 * @copyright (c) Nikolai Shcherbin 2024
 * @link https://wzm.me
**/

namespace Elgg\ActivityPub\Events\Annotations;

use Elgg\ActivityPub\Entity\ActivityPubActivity;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class OnAnnotationCreate
{
    public function __invoke(\Elgg\Event $event)
    {
        $annotation = $event->getObject();
        if (!$annotation instanceof \ElggAnnotation) {
            return;
        }

        if ($annotation->name !== 'likes') {
            return;
        }

        $entity = $annotation->getEntity();
        if (!$entity instanceof \ElggEntity) {
            return;
        }

        $user = $annotation->getOwnerEntity();

        if (!$user instanceof \ElggUser) {
            return;
        }

        if (!$user->isAdmin() && (!(bool) $user->getPluginSetting('activitypub', 'enable_activitypub') || !(bool) $user->activitypub_actor)) {
            return;
        }

        $activity_object = elgg_generate_url('view:activitypub:object', [
            'guid' => (int) $entity->guid,
        ]);

        if ($entity instanceof \Elgg\ActivityPub\Entity\FederatedObject && isset($entity->external_id)) {
            $activity_object = (string) $entity->external_id;
        }

        $activity = new ActivityPubActivity();
        $activity->owner_guid = (int) $user->guid;
        $activity->access_id = (int) $entity->access_id;
        $activity->setMetadata('activity_type', 'Like');
        $activity->setMetadata('activity_object', $activity_object);
        $activity->setMetadata('collection', ActivityPubActivity::OUTBOX);
        $activity->setMetadata('actor', elgg()->activityPubUtility->getActivityPubID($user));
        $activity->setMetadata('entity_subtype', $entity->subtype);
        $activity->setMetadata('entity_guid', (int) $entity->guid);
        $activity->setMetadata('processed', 0);
        $activity->setMetadata('status', 0);

        if ($activity->canBeQueued()) {
            $activity->setMetadata('queued', 1);
        }

        if (!$activity->save()) {
            if ((bool) elgg_get_plugin_setting('log_general_inbox_error', 'activitypub')) {
                $this->log(elgg_echo('activitypub:activitypub_activity:save:error', ['Event: OnAnnotationCreate, Object GUID: ' . $entity->guid]), 'log_general_inbox_error');
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
