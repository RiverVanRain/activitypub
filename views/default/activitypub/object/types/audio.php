<?php

$url = (string) elgg_extract('url', $vars);

if (!$url) {
	return;
}

$mimetype = (string) elgg_extract('mimetype', $vars);

echo elgg_format_element('div', [
	'rel' => (string) elgg_extract('rel', $vars),
	'class' => (string) elgg_extract('class', $vars),
	'translate' => 'no',
], "<audio preload='auto' controls><source src='{$url}' type='{$mimetype}'></audio>");
