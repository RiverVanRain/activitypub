<?php

namespace Elgg\ActivityPub\Actions\Users;

use Elgg\ActivityPub\Entity\ActivityPubActivity;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class FollowRemote
{
    public function __invoke(\Elgg\Request $request)
    {
        if (!elgg_is_logged_in()) {
            throw new \Elgg\Exceptions\Http\EntityPermissionsException();
        }

        $actor = elgg_get_logged_in_user_entity();

        $remote_friend = (string) $request->getParam('remote_friend');

        if (!$remote_friend || !(bool) elgg_get_plugin_setting('enable_activitypub', 'activitypub') || !(bool) $actor->getPluginSetting('activitypub', 'enable_activitypub') || !(bool) $actor->activitypub_actor) {
            return elgg_error_response(elgg_echo('activitypub:user:follow:error:object'));
        }

        if (!(bool) elgg()->activityPubUtility->checkDomain($actor, $remote_friend)) {
            return elgg_error_response(elgg_echo('activitypub:user:follow:error:domain'));
        }

        try {
            $activity = new ActivityPubActivity();
            $activity->owner_guid = (int) $actor->guid;
            $activity->access_id = ACCESS_PUBLIC;
            $activity->setMetadata('collection', ActivityPubActivity::OUTBOX);
            $activity->setMetadata('activity_type', 'Follow');
            $activity->setMetadata('actor', elgg()->activityPubUtility->getActivityPubID($actor));
            $activity->setMetadata('activity_object', $remote_friend);
            $activity->setMetadata('processed', 0);
            $activity->setMetadata('status', 0);

            $activity->setMetadata('queued', 1);

            if (!$activity->save()) {
                if ((bool) elgg_get_plugin_setting('log_general_inbox_error', 'activitypub')) {
                    $this->log(elgg_echo('activitypub:outbox:follow:error', [(int) $actor->guid, $remote_friend]));
                }

                return false;
            }
        } catch (\Exception $e) {
            if ((bool) elgg_get_plugin_setting('log_general_inbox_error', 'activitypub')) {
                $this->log(elgg_echo('activitypub:user:follow:error:endpoint', [$e->getMessage()]));
            }

            return elgg_error_response(elgg_echo('activitypub:user:follow:fail'));
        }

        return elgg_ok_response('', elgg_echo('activitypub:outbox:follow:success'));
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
