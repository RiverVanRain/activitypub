<?php

if (!(bool) elgg_get_plugin_setting('enable_group', 'activitypub')) {
   return;
}

$guid = (int) elgg_extract('guid', $vars);

$group = get_entity($guid);
if (!$group instanceof \ElggGroup || $group instanceof \Elgg\ActivityPub\Entity\FederatedGroup) {
	throw new \Elgg\Exceptions\Http\EntityPermissionsException();
}

elgg_push_entity_breadcrumbs($group);

echo elgg_view_page(elgg_echo('activitypub:group:settings:group', [(string) $group->getDisplayName()]), [
	'content' => elgg_view_form('group_tools/activitypub', [], [
		'entity' => $group,
	]),
	'title' => elgg_echo('activitypub:group:settings'),
]);
