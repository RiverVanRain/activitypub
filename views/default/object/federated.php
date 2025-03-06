<?php
/**
 * View for Federated objects
 *
 * @uses $vars['entity'] FederatedObject entity to show
 */

$entity = elgg_extract('entity', $vars);
if (!$entity instanceof \Elgg\ActivityPub\Entity\FederatedObject) {
	return;
}

if (!isset($vars['imprint'])) {
	$vars['imprint'] = [];
}

$vars['imprint'][] = [
	'icon_name' => 'link',
	'content' => elgg_echo('activitypub:post:federated:on', [
		elgg_view('output/url', [
			'text' => parse_url($entity->canonical_url, PHP_URL_HOST),
			'href' => (string) $entity->canonical_url,
		])
	]),
	'class' => 'elgg-listing-federated-link',
];

if (elgg_extract('full_view', $vars)) {
	$body = elgg_view('output/longtext', [
		'value' => (string) $entity->description,
		'class' => 'federated-post',
	]);

	$params = [
		'icon' => true,
		'body' => $body,
		'show_summary' => true,
		'show_navigation' => true,
	];
	$params = $params + $vars;
	
	echo elgg_view('object/elements/full', $params);
} else {
	// brief view
	$params = [
		'content' => elgg_view('output/longtext', [
			'value' => (string) $entity->excerpt,
			'class' => 'federated-post',
		]),
		'icon' => true,
		'responses' => true,
		'title' => false,
	];
	$params = $params + $vars;
	echo elgg_view('object/elements/summary', $params);
}
