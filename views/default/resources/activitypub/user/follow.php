<?php

if (!(bool) elgg_get_plugin_setting('enable_activitypub', 'activitypub')) {
    return;
}

$guid = (int) elgg_extract('guid', $vars);

$user = get_user($guid);
if (!$user || !(bool) elgg()->activityPubUtility->isEnabledUser($user)) {
    return;
}

$shell = elgg_get_config('walled_garden') ? 'walled_garden' : 'default';

echo elgg_view_page(elgg_echo('activitypub:user:follow:title', [elgg()->activityPubUtility->getActivityPubName($user)]), [
    'content' => elgg_view_form('activitypub/users/follow', [], [
        'guid' => $guid,
    ]),
    'sidebar' => false,
    'filter' => false,
], $shell);
