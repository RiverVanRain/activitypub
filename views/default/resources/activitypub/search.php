<?php

if (!(bool) elgg_get_plugin_setting('resolve_remote', 'activitypub')) {
	throw new \Elgg\Exceptions\Http\PageNotFoundException();
}

$user = elgg_get_logged_in_user_entity();

if (!(bool) elgg_get_plugin_setting('enable_activitypub', 'activitypub') || !(bool) $user->getPluginSetting('activitypub', 'enable_activitypub') || !(bool) $user->activitypub_actor) {
	throw new \Elgg\Exceptions\Http\EntityPermissionsException();
}

$query = (string) elgg_extract('query', get_input('query'));

$content = elgg_view_form('activitypub/search', [
	'id' => 'activitypub-search-form',
], [
	'query' => $query
]);

$content .= elgg_format_element('ul', ['class' => 'elgg-list activitypub-search-form-results']);

echo elgg_view_page(elgg_echo('activitypub:search'), [
	'content' => $content,
]);
