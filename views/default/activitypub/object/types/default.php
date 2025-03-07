<?php

$url = (string) elgg_extract('url', $vars);
if (!$url) {
    return;
}

$type = (string) elgg_extract('type', $vars, 'Document');
if (!$type) {
    return;
}

$mediaType = (string) elgg_extract('mediaType', $vars);

if (!empty($mediaType)) {
    if (strpos($mediaType, 'image/') === 0) {
        $type = 'Image';
    } elseif (strpos($mediaType, 'video/') === 0) {
        $type = 'Video';
    } elseif (strpos($mediaType, 'audio/') === 0) {
        $type = 'Audio';
    }
}

echo match ($type) {
    'Image' => elgg_view('activitypub/object/types/image', [
        'url' => $url,
        'title' => (string)elgg_extract('title', $vars, null),
        'width' => (string)elgg_extract('width', $vars, null),
        'height' => (string)elgg_extract('height', $vars, null),
        'class' => 'mtm',
    ]),
    'Video' => elgg_view('activitypub/object/types/video', [
        'url' => $url,
        'mimetype' => $mediaType,
        'class' => 'mtm',
    ]),
    'Audio' => elgg_view('activitypub/object/types/audio', [
        'url' => $url,
        'mimetype' => $mediaType,
        'class' => 'mtm',
    ]),
    'Document' => elgg_view('activitypub/object/types/document', [
        'url' => $url,
        'class' => 'mtm',
    ]),
};
