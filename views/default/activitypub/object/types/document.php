<?php

$url = (string) elgg_extract('url', $vars);

if (!$url) {
    return;
}

echo elgg_format_element('div', [
    'rel' => (string) elgg_extract('rel', $vars),
    'class' => (string) elgg_extract('class', $vars),
    'translate' => 'no',
], elgg_view('output/url', [
    'href' => $url,
    'text' => elgg_echo('activitypub:attachment'),
]));
