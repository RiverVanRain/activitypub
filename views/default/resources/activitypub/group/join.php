<?php

if (!(bool) elgg_get_plugin_setting('enable_group', 'activitypub')) {
    return;
}

$guid = (int) elgg_extract('guid', $vars);

$group = get_entity($guid);
if (!$group instanceof \ElggGroup || !(bool) $group->activitypub_enable || !(bool) $group->activitypub_actor) {
    throw new \Elgg\Exceptions\Http\EntityPermissionsException();
}

elgg_push_entity_breadcrumbs($group);

$shell = elgg_get_config('walled_garden') ? 'walled_garden' : 'default';

echo elgg_view_page(elgg_echo('groups:join'), [
    'content' => elgg_view_form('activitypub/groups/join', [], [
        'guid' => $guid,
    ]),
    'sidebar' => false,
    'filter' => false,
], $shell);
