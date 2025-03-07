<?php

$url = (string) elgg_extract('url', $vars);

if (!$url) {
    return;
}

$img = elgg_view('output/img', [
    'class' => 'elgg-photo',
    'src' => $url,
    'alt' => (string) elgg_extract('title', $vars),
    'width' => (string) elgg_extract('width', $vars),
    'height' => (string) elgg_extract('height', $vars),
    'loading' => 'lazy',
]);

echo elgg_format_element('div', ['class' => 'file-photo', 'translate' => 'no'], elgg_view('output/url', [
    'text' => $img,
    'href' => $url,
    'class' => (string) elgg_extract('class', $vars),
    'rel' => (string) elgg_extract('rel', $vars),
]));
