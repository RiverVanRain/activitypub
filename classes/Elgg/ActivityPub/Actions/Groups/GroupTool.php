<?php

namespace Elgg\ActivityPub\Actions\Groups;

class GroupTool
{
    public function __invoke(\Elgg\Request $request)
    {
        if (!(bool) elgg_get_plugin_setting('enable_group', 'activitypub')) {
            return;
        }

        $group = $request->getEntityParam();

        if (!$group instanceof \ElggGroup || $group instanceof \Elgg\ActivityPub\Entity\FederatedGroup || !$group->canEdit()) {
            throw new \Elgg\Exceptions\Http\EntityPermissionsException();
        }

        $group->activitypub_enable = (bool) $request->getParam('activitypub_enable');
        $group->enable_discoverable = (bool) $request->getParam('enable_discoverable');
        $group->activitypub_blocked_domains = (string) $request->getParam('activitypub_whitelisted_domains');
        $group->activitypub_blocked_domains = (string) $request->getParam('activitypub_blocked_domains');

        if (!$group->save()) {
            return elgg_error_response(elgg_echo('activitypub:group:settings:error'));
        }

        return elgg_ok_response('', elgg_echo('activitypub:group:settings:save'));
    }
}
