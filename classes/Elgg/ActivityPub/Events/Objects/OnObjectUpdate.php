<?php

/**
 * ActivityPub
 * @author Nikolai Shcherbin
 * @license GNU Affero General Public License version 3
 * @copyright (c) Nikolai Shcherbin 2024
 * @link https://wzm.me
**/

namespace Elgg\ActivityPub\Events\Objects;

use Elgg\ActivityPub\Entity\ActivityPubActivity;
use Elgg\ActivityPub\Entity\FederatedObject;

class OnObjectUpdate
{
    public function __invoke(\Elgg\Event $event)
    {
        $activity = $event->getObject();

        if (!$activity instanceof ActivityPubActivity) {
            return;
        }

        $user = $activity->getOwnerEntity();

        if (!$user instanceof \ElggUser) {
            return;
        }

        $entities = elgg_call(ELGG_IGNORE_ACCESS, function () use ($activity, $user) {
            return elgg_get_entities([
                'type' => 'object',
                'subtype' => [FederatedObject::SUBTYPE, 'comment'],
                'owner_guid' => (int) $user->guid,
                'metadata_name_value_pairs' => [
                    [
                        'name' => 'external_id',
                        'value' => (string) $activity->getActivityObject(),
                    ],
                ],
                'limit' => 1,
            ]);
        });

        if (!empty($entities)) {
            foreach ($entities as $entity) {
                $payload = json_decode($activity->getPayload(), true);

                $summary = (string) $entity->excerpt ?? ' ';

                if (!empty($payload['object']['summary'])) {
                    $summary = (string) $payload['object']['summary'];
                }

                $content = (string) $entity->description ?? ' ';

                if (!empty($payload['object']['content'])) {
                    $content = $summary = (string) $payload['object']['content'];
                }

                if (is_callable('mb_convert_encoding')) {
                    $excerpt = mb_convert_encoding($summary, 'HTML-ENTITIES', 'UTF-8');
                    $description = mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8');
                } else {
                    $excerpt = $summary;
                    $description = $content;
                }

                $entity->excerpt = elgg_sanitize_input($excerpt);
                $entity->description = elgg_sanitize_input($description);

                if (!empty($payload['object']['attachment'])) {
                    $attachments = [];

                    foreach ($payload['object']['attachment'] as $attachment) {
                        if (!isset($attachment['type']) || !isset($attachment['url'])) {
                            continue;
                        }
                        if (!in_array($attachment['type'], ['Audio', 'Document', 'Image', 'Video'], true)) {
                            continue;
                        }

                        $title = !empty($attachment['name']) ? (string) $attachment['name'] : (string) $attachment['url'];

                        $attachments[] = [
                            'type' => (string) $attachment['type'],
                            'mediaType' => !empty($attachment['mediaType']) ? (string) $attachment['mediaType'] : null,
                            'url' => (string) $attachment['url'],
                            'title' => $title,
                            'width' => !empty($attachment['width']) ? (string) $attachment['width'] : null,
                            'height' => !empty($attachment['height']) ? (string) $attachment['height'] : null,
                        ];
                    }

                    if (!empty($attachments)) {
                        $entity->attachments = elgg_view('activitypub/object/attachments', [
                            'attachments' => $attachments,
                        ]);
                    }
                }

                $entity->save();
            }
        }
    }
}
