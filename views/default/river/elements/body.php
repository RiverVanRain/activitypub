<?php
/**
 * Body of river item
 *
 * @uses $vars['item']        ElggRiverItem
 * @uses $vars['summary']     Alternate summary (the short text summary of action)
 * @uses $vars['message']     Optional message (usually excerpt of text)
 * @uses $vars['attachments'] Optional attachments (displaying icons or other non-text data)
 * @uses $vars['responses']   Alternate respones (comments, replies, etc.)
 */

$item = elgg_extract('item', $vars);
if (!$item instanceof \ElggRiverItem) {
	return;
}

// metadata (eg. menus)
$metadata = elgg_extract('metadata', $vars);
if (!isset($metadata)) {
	$metadata = elgg_view_menu('river', [
		'item' => $item,
		'prepare_dropdown' => true,
	]);
	
	$object = $item->getObjectEntity();
	if ($object) {
		$metadata .= elgg_view_menu('social', [
			'entity' => $object,
			'item' => $item,
			'class' => 'elgg-menu-hz',
		]);
	}
}

if (!empty($metadata)) {
	echo elgg_format_element('div', [
		'class' => 'elgg-river-metadata',
	], $metadata);
}

// summary
$timestamp = elgg_view_friendly_time($item->getTimePosted());
if (!empty($timestamp)) {
	$timestamp = elgg_format_element('span', ['class' => 'elgg-river-timestamp'], $timestamp);
}

$summary = elgg_extract('summary', $vars);
if (!isset($summary)) {
	$summary = elgg_view('river/elements/summary', $vars);
}

if ($summary === false) {
	$subject = $item->getSubjectEntity();
	if ($subject instanceof ElggEntity) {
		$summary = elgg_view_entity_url($subject, ['class' => 'elgg-river-subject']);
	}
}

// federated
$canonical_url = false;

if (elgg_is_active_plugin('activitypub') && !elgg_is_active_plugin('theme')) {
	if ($object instanceof \Elgg\ActivityPub\Entity\FederatedObject && (string) $object->canonical_url) {
		$canonical_url = elgg_format_element('span', ['class' => 'elgg-river-federated-link elgg-subtext'], elgg_echo('activitypub:post:federated:on', [
			elgg_view('output/url', [
				'text' => parse_url($object->canonical_url, PHP_URL_HOST),
				'href' => (string) $object->canonical_url,
			])
		]));
	}
}

$summary = trim("{$summary} {$timestamp} {$canonical_url}");

if (!empty($summary)) {
	echo elgg_format_element('div', ['class' => 'elgg-river-summary'], $summary);
}

// message (eg excerpt)
$message = (string) elgg_extract('message', $vars);
if (!empty($message)) {
	echo elgg_format_element('div', ['class' => 'elgg-river-message',], $message);
}

// attachments
$attachments = elgg_extract('attachments', $vars);
if (!empty($attachments)) {
	echo elgg_format_element('div', ['class' => 'elgg-river-attachments'], $attachments);
}

// responses (eg. comments)
$responses = elgg_view('river/elements/responses', $vars);
if (!empty($responses)) {
	echo elgg_format_element('div', ['class' => 'elgg-river-responses',], $responses);
}
