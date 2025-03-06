<?php

$entity = get_entity((int) get_input('guid'));
if (!$entity instanceof \ElggObject) {
	return;
}

$svc = elgg()->activityPubUtility;
$subtypes = $svc->getDynamicSubTypes();

if (!(bool) elgg_get_plugin_setting("can_activitypub:object:{$entity->subtype}", 'activitypub') && !in_array($entity->subtype, $subtypes)) {
	return;
}

echo elgg_format_element('link', [
	'rel' => 'alternate',
	'type' => 'application/activity+json',
	'href' => (string) $entity->getURL(),
]);

//Publisher
$user = $entity->getOwnerEntity();

if ($user instanceof \ElggUser && (bool) $user->getPluginSetting('activitypub', 'enable_activitypub') && (bool) $user->activitypub_actor) {
	echo elgg_format_element('meta', [
		'name' => 'fediverse:creator',
		'property' => 'fediverse:creator',
		'content' => '@' . (string) $user->activitypub_name,
	]);
}
