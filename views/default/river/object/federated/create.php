<?php

/**
 * FederatedObject river view.
 */

$item = elgg_extract('item', $vars);
if (!$item instanceof \ElggRiverItem) {
    return;
}

$object = $item->getObjectEntity();
if (!$object instanceof \Elgg\ActivityPub\Entity\FederatedObject) {
    return;
}

$vars['message'] = (string) $object->excerpt;

echo elgg_view('river/elements/layout', $vars);
