<?php

$entity = elgg_get_plugin_from_id('activitypub');

elgg_import_esm('forms/admin/activitypub/settings');

// Core types
$subtypes = [
	'blog' => 'Article',
	'comment' => 'Note',
	'messages' => 'Note',
	'river' => 'Note',
	'thewire' => 'Note',
	'file' => [
		'Audio',
		'Document',
		'Image',
		'Video',
	],
	'event' => 'Event',
	'poll' => 'Page',
	'album' => 'Page',
	'photo' => 'Image',
	'topic' => 'Page',
	'topic_post' => 'Article',
];

if (!elgg_is_active_plugin('blog')) {
	unset($subtypes['blog']);
}

if (!elgg_is_active_plugin('messages')) {
	unset($subtypes['messages']);
}

if (!elgg_is_active_plugin('river')) {
	unset($subtypes['river']);
}

if (!elgg_is_active_plugin('thewire')) {
	unset($subtypes['thewire']);
}

if (!elgg_is_active_plugin('file')) {
	unset($subtypes['file']);
}

if (!elgg_is_active_plugin('event_manager')) {
	unset($subtypes['event']);
}

if (!elgg_is_active_plugin('poll')) {
	unset($subtypes['poll']);
}

if (!elgg_is_active_plugin('gallery')) {
	unset($subtypes['album']);
	unset($subtypes['photo']);
}

if (!elgg_is_active_plugin('topics')) {
	unset($subtypes['topic']);
	unset($subtypes['topic_post']);
}

ob_start();
foreach ($subtypes as $subtype => $object) {
	if (is_array($object)) {
		$object = implode(', ', $object);
	}
	
	echo elgg_view_field([
		'#type' => 'checkbox',
		'name' => "params[can_activitypub:object:{$subtype}]",
		'value' => 1,
		'default' => 0,
		'checked' => (bool) $entity->{"can_activitypub:object:{$subtype}"},
		'#label' => elgg_echo("collection:object:{$subtype}"),
		'#help' => elgg_echo('activitypub:object:aptype', [$object]),
		'switch' => true,
	]);
}
$inputs = ob_get_clean();

echo elgg_view('elements/forms/field', [
	'input' => $inputs,
	'label' => elgg_echo('settings:activitypub:types:core'),
	'class' => 'fa-1x3 elgg-loud',
]);

// Dynamic types
$settings = unserialize($entity->getSetting('dynamic_types'));

if (!$settings) {
	$settings = [
		'dynamic' => [
			'policy' => [
				[
					'subtype' => '',
					'aptype' => '',
					'can_activitypub' => '0',
				]
			],
		],
	];
}

$objects = (array) elgg_extract('object', elgg_entity_types_with_capability('searchable'), []);

$options_values = [
	'' => elgg_echo('settings:activitypub:types:select')
];

foreach ($objects as $object) {
	if (in_array($object, ['blog', 'comment', 'messages', 'river', 'thewire', 'file', 'event', 'poll', 'album', 'photo', 'topic', 'topic_post', 'federated'])) {
		continue;
	}
	
	$options_values[$object] = [
		'text' => elgg_echo("collection:object:$object"),
		'value' => $object,
	];
}

$policy = $settings['dynamic']['policy'];

if (is_array($policy)) {
	echo elgg_view_field([
		'#html' => elgg_format_element('h3', ['class' => 'mtm mbm elgg-loud'], elgg_echo('settings:activitypub:types:dynamic')),
	]);
	
	foreach ($policy as $p) {
		if (is_array($p['subtype'])) {
			$count = count($p['subtype']);
		} else {
			$count = 1;
		}
		
		for ($i = 0; $i < $count; $i++) {
			$fields = [
				[
					'#type' => 'select',
					'name' => "dynamic_types[dynamic][policy][subtype][]",
					'value' => [$p][$i]['subtype'],
					'#class' => 'elgg-col elgg-col-1of4',
					'#label' => elgg_echo('settings:activitypub:types:dynamic:subtype'),
					'options_values' => $options_values,
					'no_js' => true,
				],
				[
					'#type' => 'select',
					'name' => "dynamic_types[dynamic][policy][aptype][]",
					'value' => [$p][$i]['aptype'],
					'#class' => 'elgg-col elgg-col-1of4',
					'#label' => elgg_echo('settings:activitypub:types:dynamic:aptype'),
					'options_values' => [
						'' => elgg_echo('settings:activitypub:types:select'),
						'Note' => 'Note',
						'Article' => 'Article',
						'Page' => 'Page',
						'Event' => 'Event',
						'Document' => 'Document',
						'Image' => 'Image',
						'Audio' => 'Audio',
						'Video' => 'Video',
					],
					'no_js' => true,
				],
				[
					'#type' => 'select',
					'name' => "dynamic_types[dynamic][policy][can_activitypub][]",
					'value' => [$p][$i]['can_activitypub'],
					'#class' => 'elgg-col elgg-col-1of4',
					'#label' => elgg_echo('status:enabled'),
					'options_values' => [
						'0' => elgg_echo('option:no'),
						'1' => elgg_echo('option:yes'),
					],
					'no_js' => true,
				],
				[
					'#type' => 'link',
					'text' => elgg_view_icon('plus', ['class' => 'float mrs']),
					'class' => 'ap-icon-plus elgg-state elgg-state-success float mrs',
					'#class' => 'activitypub-input-icon',
					'href' => false,
				],
				[
					'#type' => 'link',
					'text' => elgg_view_icon('minus', ['class' => 'float']),
					'class' => 'ap-icon-minus elgg-state elgg-state-danger float',
					'#class' => 'activitypub-input-icon',
					'href' => false,
				],
			];
			
			echo elgg_view_field([
				'#type' => 'fieldset',
				'class' => 'elgg-grid ap-policy mbm',
				'fields' => $fields,
				'align' => 'horizontal',
			]);
		}
	}
}

echo elgg_view_field([
	'#type' => 'hidden',
	'name' => 'plugin_id',
	'value' => 'activitypub',
]);

$footer = elgg_view_field([
	'#type' => 'submit',
	'text' => elgg_echo('save'),
]);

elgg_set_form_footer($footer);
