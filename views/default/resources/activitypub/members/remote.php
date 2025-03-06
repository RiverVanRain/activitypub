<?php
/**
 * Show a list of all federated users
 */

echo elgg_view_page(elgg_echo('members:title:remote'), [
	'content' => elgg_list_entities([
		'type' => 'user',
		'subtype' => 'federated',
	]),
	'sidebar' => elgg_view('members/sidebar'),
	'filter_id' => 'members',
	'filter_value' => 'remote',
]);
