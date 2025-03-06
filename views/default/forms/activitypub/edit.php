<?php

$guid = (int) elgg_extract('guid', $vars, 0);

$entity = get_entity($guid);

if (!$entity instanceof \Elgg\ActivityPub\Entity\ActivityPubActivity) {
	throw new \Elgg\Exceptions\Http\EntityNotFoundException();
}

echo elgg_view_field([
	'#type' => 'checkbox',
	'#label' => elgg_echo('activitypub:activities:queued'),
	'name' => 'queued',
	'checked' => (bool) $entity->queued,
	'switch' => true,
]);

echo elgg_view_field([
	'#type' => 'checkbox',
	'#label' => elgg_echo('activitypub:activities:processed'),
	'name' => 'processed',
	'checked' => (bool) $entity->processed,
	'switch' => true,
]);

echo elgg_view_field([
	'#type' => 'checkbox',
	'#label' => elgg_echo('activitypub:activities:status'),
	'name' => 'status',
	'checked' => (bool) $entity->status,
	'switch' => true,
]);

//submit
echo elgg_view('input/submit', [
	'text' => elgg_echo('save'),
]);

//hiddens
elgg_set_form_footer(elgg_view_field([
	'#type' => 'hidden',
	'name' => 'guid',
	'value' => (int) $entity->guid,
]));
