<?php

$guid = (int) elgg_extract('guid', $vars);
elgg_entity_gatekeeper($guid, 'object', 'federated');

if (elgg_is_active_plugin('theme')) {
    echo elgg_view_resource('post/view', [
        'guid' => $guid
    ]);
    return;
}

$entity = get_entity($guid);

elgg_push_entity_breadcrumbs($entity);

echo elgg_view_page((string) $entity->getDisplayName(), [
    'content' => elgg_view_entity($entity, [
        'full_view' => true,
        'show_responses' => true,
    ]),
    'entity' => $entity,
], 'default', [
    'entity' => $entity,
]);
