<?php
$group = elgg_extract('entity', $vars);
if (!$group instanceof \ElggGroup || !$group->canEdit()) {
	return;
}

elgg_import_esm('js/activitypub/groupsettings');

echo elgg_view_field([
	'#type' => 'checkbox',
	'#label' => elgg_echo('groups:tool:activitypub:settings:enable_activitypub'),
	'#help' => elgg_echo('groups:tool:activitypub:settings:enable_activitypub:help'),
	'name' => 'activitypub_enable',
	'default' => 0,
	'value' => 1,
	'checked' => (bool) $group->activitypub_enable,
	'switch' => true,
	'id' => 'groupsettings-enable-activitypub',
]);

if ((bool) $group->activitypub_enable && (bool) $group->activitypub_actor) {
	$actor_name = (string) $group->activitypub_name;
	
	echo elgg_format_element('h4', ['class' => 'mbm'], elgg_echo('activitypub:user:is_actor:header', [$actor_name, $actor_name]));
}

//check global domains settings
$global_whitelisted = false;
$global_whitelisted_domains = (string) elgg_get_plugin_setting('activitypub_global_whitelisted_domains', 'activitypub');

if (!empty($global_whitelisted_domains)) {
	$global_whitelisted =  elgg_format_element('div', [], elgg_echo('activitypub:global_whitelisted_domains') . ': ' . $global_whitelisted_domains);
}

$global_blocked = false;
$global_blocked_domains = (string) elgg_get_plugin_setting('activitypub_global_blocked_domains', 'activitypub');

if (!empty($global_blocked_domains)) {
	$global_blocked =  elgg_format_element('div', [], elgg_echo('activitypub:global_blocked_domains') . ': ' . $global_blocked_domains);
}

//check group whitelisted domains
$whitelisted_domains = (string) $group->activitypub_whitelisted_domains;
$whitelisted_domains = preg_split('/\\r\\n?|\\n/', $whitelisted_domains);
$whitelisted_domains = array_filter($whitelisted_domains);

//check group blocked domains
$blocked_domains = (string) $group->activitypub_blocked_domains;
$blocked_domains = preg_split('/\\r\\n?|\\n/', $blocked_domains);
$blocked_domains = array_filter($blocked_domains);

echo elgg_view_field([
	'#type' => 'fieldset',
	'class' => (bool) $group->activitypub_enable ? '' : 'hidden',
	'id' => 'groupsettings-activitypub',
	'fields' => [
		// Discoverability flag
		[
			'#type' => 'checkbox',
			'#label' => elgg_echo('groups:tool:activitypub:settings:enable_discoverable'),
			'#help' => elgg_echo('groups:tool:activitypub:settings:enable_discoverable:help'),
			'name' => 'enable_discoverable',
			'default' => 0,
			'value' => 1,
			'checked' => (bool) $group->enable_discoverable,
			'switch' => true,
		],
		[
			'#type' => 'fieldset',
			'legend' => elgg_echo('groups:tool:activitypub:settings:domains'),
			'fields' => [
				[
					'#html' => elgg_format_element('div', ['class' => 'elgg-field-help elgg-text-help'], elgg_echo('groups:tool:activitypub:settings:domains:help')),
				],
				[
					'#type' => 'plaintext',
					'#label' => elgg_echo('groups:tool:activitypub:settings:whitelisted_domains'),
					'#help' => elgg_echo('groups:tool:activitypub:settings:whitelisted_domains:help', [$global_whitelisted]),
					'name' => 'activitypub_whitelisted_domains',
					'value' => !empty($whitelisted_domains) ? implode("\r\n", $whitelisted_domains) : '',
				],
				[
					'#type' => 'plaintext',
					'#label' => elgg_echo('groups:tool:activitypub:settings:blocked_domains'),
					'#help' => elgg_echo('groups:tool:activitypub:settings:blocked_domains:help', [$global_blocked]),
					'name' => 'activitypub_blocked_domains',
					'value' => !empty($blocked_domains) ? implode("\r\n", $blocked_domains) : '',
				],
			],
		],
	],
]);
$footer = elgg_view_field([
	'#type' => 'hidden',
	'name' => 'guid',
	'value' => (int) $group->guid,
]);

$footer .= elgg_view_field([
	'#type' => 'submit',
	'text' => elgg_echo('save'),
]);

elgg_set_form_footer($footer);
