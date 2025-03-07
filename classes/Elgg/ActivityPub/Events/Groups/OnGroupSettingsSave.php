<?php

/**
 * ActivityPub
 * @author Nikolai Shcherbin
 * @license GNU Affero General Public License version 3
 * @copyright (c) Nikolai Shcherbin 2024
 * @link https://wzm.me
**/

namespace Elgg\ActivityPub\Events\Groups;

class OnGroupSettingsSave
{
    public function __invoke(\Elgg\Event $event)
    {

        $request = $event->getParam('request');
        $group = get_entity((int) $request->getParam('guid'));

        if (!$group instanceof \ElggGroup || !$request instanceof \Elgg\Request) {
            return null;
        }

        if (!(bool) $request->getParam('activitypub_enable')) {
            $group->setMetadata('activitypub_actor', 0);

            return true;
        }

        $activitypub_name = mb_strtolower((string) $group->getDisplayName());

        $activitypub_name = preg_replace('/[^\p{L}\p{N}]/u', '', $activitypub_name);

        while (elgg_get_user_by_username($activitypub_name)) {
            $activitypub_name = $activitypub_name . '_' . substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 1, 12);
        }

        $activitypub_name = $activitypub_name . '@' . elgg_get_site_entity()->getDomain();

        $name = strstr((string) $activitypub_name, '@', true);

        $svc = elgg()->activityPubSignature;

        if ($svc->generateKeys($name)) {
            $group->setMetadata('activitypub_actor', 1);
            $group->setMetadata('activitypub_name', $activitypub_name);
            $group->setMetadata('activitypub_groupname', $name);

            return true;
        } else {
            return elgg_error_response(elgg_echo('activitypub:keys:generate:entity:error'));
        }
    }
}
