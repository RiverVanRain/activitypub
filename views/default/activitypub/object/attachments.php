<?php

$attachments = (array) elgg_extract('attachments', $vars, []);

if (empty($attachments)) {
	return;
}

echo '<ul class="elgg-gallery activitypub-attachments-gallery">';

foreach ($attachments as $attachment) {
	echo '<li class="elgg-item">';
	
	echo elgg_view('activitypub/object/types/default', [
		'type' => (string) $attachment['type'],
		'mediaType' => (string) $attachment['mediaType'],
		'url' => (string) $attachment['url'],
		'title' => (string) $attachment['title'],
		'width' => (string) $attachment['width'],
		'height' => (string) $attachment['height'],
	]);
	
	echo '</li>';
}

echo '</ul>';

