<?php

/**
 * Show a list of all local users
 */

echo elgg_view_page(elgg_echo('members:title:local'), [
    'content' => elgg_list_entities([
        'type' => 'user',
        'subtype' => 'user',
    ]),
    'sidebar' => elgg_view('members/sidebar'),
    'filter_id' => 'members',
    'filter_value' => 'local',
]);
