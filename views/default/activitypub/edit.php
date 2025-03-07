<?php

elgg_admin_gatekeeper();

$guid = (int) get_input('guid');

$entity = get_entity($guid);
if (!$entity instanceof \Elgg\ActivityPub\Entity\ActivityPubActivity) {
    throw new \Elgg\Exceptions\Http\EntityNotFoundException();
}

$modaltitle = elgg_format_element('h3', ['class' => 'modal-title'], elgg_echo('activitypub:activitypub_activity:edit', [$guid]));
$header = elgg_format_element('div', ['class' => 'modal-header'], $modaltitle);

$form = elgg_format_element('div', [], elgg_view_form('activitypub/edit', [], ['guid' => $guid]));

echo elgg_format_element('div', [], $header . $form);
