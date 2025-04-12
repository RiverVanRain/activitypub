<?php

$guid = (int) elgg_extract('guid', $vars);

$user = get_user($guid);
if (!$user || !(bool) elgg()->activityPubUtility->isEnabledUser($user)) {
    return;
}

echo elgg_view_field([
    '#type' => 'text',
    '#label' => elgg_echo('activitypub:user:follow:label'),
    '#help' => elgg_echo('activitypub:user:follow:help', [elgg()->activityPubUtility->getActivityPubName($user)]),
    'name' => 'handle',
    'value' => (string) elgg_extract('handle', $vars, ''),
    'autofocus' => true,
    'required' => true,
]);

echo elgg_view_field([
    '#type' => 'hidden',
    'name' => 'local_actor',
    'value' => elgg()->activityPubUtility->getActivityPubName($user),
]);

echo elgg_view_field([
    '#type' => 'hidden',
    'name' => 'guid',
    'value' => $guid,
]);

$footer = elgg_view_field([
    '#type' => 'submit',
    'text' => elgg_echo('activitypub:user:follow:submit'),
]);

elgg_set_form_footer($footer);
