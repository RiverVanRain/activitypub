<?php

elgg_import_esm('js/activitypub/search');

echo elgg_view_field([
    '#type' => 'text',
    '#label' => elgg_echo('activitypub:search:label'),
    '#help' => elgg_echo('activitypub:search:help'),
    'name' => 'query',
    'autofocus' => true,
    'required' => true,
]);

$footer = elgg_view_field([
    '#type' => 'submit',
    'text' => elgg_echo('search'),
]);

elgg_set_form_footer($footer);
