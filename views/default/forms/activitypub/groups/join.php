<?php

$guid = (int) elgg_extract('guid', $vars);

$group = get_entity($guid);
if (!$group instanceof \ElggGroup || !(bool) $group->activitypub_enable || !(bool) $group->activitypub_actor) {
    return;
}

echo elgg_view_field([
    '#type' => 'text',
    '#label' => elgg_echo('activitypub:group:join:label'),
    '#help' => elgg_echo('activitypub:group:join:help', [elgg()->activityPubUtility->getActivityPubName($group)]),
    'name' => 'handle',
    'value' => (string) elgg_extract('handle', $vars, ''),
    'autofocus' => true,
    'required' => true,
]);

echo elgg_view_field([
    '#type' => 'hidden',
    'name' => 'local_actor',
    'value' => elgg()->activityPubUtility->getActivityPubName($group),
]);

echo elgg_view_field([
    '#type' => 'hidden',
    'name' => 'guid',
    'value' => $guid,
]);

$footer = elgg_view_field([
    '#type' => 'submit',
    'text' => elgg_echo('activitypub:group:join:submit'),
]);

elgg_set_form_footer($footer);
