<?php

namespace Elgg\ActivityPub\Controller;

use Elgg\ActivityPub\Entity\ActivityPubActivity;
use Elgg\ActivityPub\Services\ResolveService;

class InteractionsController
{
    /**
     * Interactions routing callback.
     *
     * @return \Elgg\Http\Response
     */
    public function __invoke(\Elgg\Request $request): \Elgg\Http\Response
    {
        $url = $request->getParam('uri');

        if (empty($url) || $url === '{uri}') {
            return elgg_error_response(elgg_echo('activitypub:interactions:error'));
        }

        $object = ResolveService::getRemoteObject($url);

        if (!$object || !isset($object['@context']) || !isset($object['type'])) {
            return elgg_error_response(elgg_echo('activitypub:interactions:no_object'));
        }

        $activity_type = match ($object['type']) {
            'Person' => 'Follow',
            'Group' => 'Join',
            default => false
        };

        if (!$activity_type) {
            return elgg_error_response(elgg_echo('activitypub:interactions:no_type'));
        }

        $uri = $url;

        if (!empty($object['id'])) {
            $uri = $object['id'];
        }

        $actor = elgg_get_logged_in_user_entity();

        if (!(bool) elgg_get_plugin_setting('enable_activitypub', 'activitypub') || !(bool) $actor->getPluginSetting('activitypub', 'enable_activitypub') || !(bool) $actor->activitypub_actor) {
            return elgg_error_response(elgg_echo('activitypub:interactions:no_valid'));
        }

        if (!(bool) elgg()->activityPubUtility->checkDomain($actor, $uri)) {
            return elgg_error_response(elgg_echo('activitypub:interactions:domain'));
        }

        $activity = new ActivityPubActivity();
        $activity->owner_guid = (int) $actor->guid;
        $activity->access_id = ACCESS_PUBLIC;
        $activity->setMetadata('collection', ActivityPubActivity::OUTBOX);
        $activity->setMetadata('activity_type', $activity_type);
        $activity->setMetadata('actor', elgg()->activityPubUtility->getActivityPubID($actor));
        $activity->setMetadata('activity_object', $object['id']);
        $activity->setMetadata('processed', 0);
        $activity->setMetadata('status', 0);

        $activity->setMetadata('queued', 1);

        if (!$activity->save()) {
            return elgg_error_response(elgg_echo('activitypub:interactions:activity'));
        }

        return elgg_ok_response('', elgg_echo('activitypub:interactions:success', [$activity_type]), (string) $actor->getURL());
    }
}
