<?php

/**
 * ActivityPub
 * @author Nikolai Shcherbin
 * @license GNU Affero General Public License version 3
 * @copyright (c) Nikolai Shcherbin 2024
 * @link https://wzm.me
**/

namespace Elgg\ActivityPub\Controller;

use Elgg\ActivityPub\Entity\ActivityPubActivity;
use Elgg\ActivityPub\Helpers\JsonLdHelper;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class InboxController
{
    /**
     * Inbox routing callback.
     */
    public function __invoke(\Elgg\Request $request): \Elgg\Http\Response
    {
        $entity = $request->getEntityParam();

        if (!$entity instanceof \ElggUser && !$entity instanceof \ElggGroup) {
            throw new \Elgg\Exceptions\Http\PageNotFoundException();
        }

        if ($entity instanceof \ElggUser && (!(bool) $entity->getPluginSetting('activitypub', 'enable_activitypub') || !(bool) $entity->activitypub_actor)) {
            throw new \Elgg\Exceptions\Http\PageNotFoundException();
        }

        if ($entity instanceof \ElggGroup && (!(bool) $entity->activitypub_enable || !(bool) $entity->activitypub_actor)) {
            throw new \Elgg\Exceptions\Http\PageNotFoundException();
        }

        $status = 400;

        $response = new \Elgg\Http\ErrorResponse('', $status);

        if ($request->getMethod() !== 'POST') {
            return $response;
        }

        $payload = json_decode((string) $request->getHttpRequest()->getContent(), true);

        if (empty($payload)) {
            if ((bool) elgg_get_plugin_setting('log_general_inbox_error', 'activitypub')) {
                $this->log(elgg_echo('activitypub:inbox:general:payload:empty', [(string) $request->getHttpRequest()->getContent()]), 'log_general_inbox_error');
            }
            return $response;
        }

        if (!JsonLdHelper::isSupportedContext($payload)) {
            return null;
        }

        if ($payload['type'] !== 'Delete' && (bool) elgg_get_plugin_setting('log_general_inbox_error', 'activitypub')) {
            $this->log(elgg_echo('activitypub:inbox:general:payload', [(string) $request->getHttpRequest()->getContent()]), 'log_general_inbox_error');
        }

        $actor = JsonLdHelper::getValueOrId($payload['actor']);
        $id = JsonLdHelper::getValueOrId($payload['id']);

        if (empty($actor) || empty($id)) {
            if ((bool) elgg_get_plugin_setting('log_general_inbox_error', 'activitypub')) {
                $this->log(elgg_echo('activitypub:inbox:general:payload:empty:actor', [(string) $request->getHttpRequest()->getContent()]), 'log_general_inbox_error');
            }

            return $response;
        }

        // POST
        try {
            if ((bool) elgg()->activityPubUtility->checkDomain($entity, $actor)) {
                // The signature check is used to set the status of the activity.
                // There is a big chance some might fail depending how the request is
                // signed and which RFC version is used. In case the verification
                // fails, we allow posts to be followed in case the actor is a
                // followee of the current user.
                try {
                    $published = elgg()->activityPubSignature->verifySignature($request->getHttpRequest(), $actor, elgg()->activityPubUtility->getServer());

                    if (!$published && isset($payload['type']) && in_array($payload['type'], elgg()->activityPubUtility->getTimelineTypes())) {
                        $published = $this->isFollowee($actor, (int) $entity->guid);
                    }
                } catch (\Exception $e) {
                    $published = false;
                    if ((bool) elgg_get_plugin_setting('log_error_signature', 'activitypub')) {
                        $this->log(elgg_echo('activitypub:inbox:signature:exception', [$e->getMessage()]), 'log_error_signature');
                    }
                }

                // Get the object.
                $object = $this->getObject($payload);

                elgg_call(ELGG_IGNORE_ACCESS | ELGG_SHOW_DISABLED_ENTITIES, function () use ($entity, $id, &$payload, $actor, $object, $published, $request) {
                    if ($entity instanceof \ElggGroup) {
                        if ($payload['type'] === 'Follow') {
                            $payload['type'] = 'Join';
                        }

                        if ($payload['type'] === 'Undo') {
                            $payload['type'] = 'Leave';
                        }
                    }

                    $activity = new ActivityPubActivity();
                    $activity->owner_guid = (int) $entity->guid;
                    $activity->setMetadata('collection', ActivityPubActivity::INBOX);
                    $activity->setMetadata('external_id', $id);
                    $activity->setMetadata('activity_type', (string) $payload['type']);
                    $activity->setMetadata('actor', $actor);
                    $activity->setMetadata('activity_object', $object);
                    $activity->setMetadata('payload', (string) $request->getHttpRequest()->getContent());
                    $activity->setMetadata('status', 0);

                    if (is_array($payload['object'])) {
                        if (!empty($payload['object']['content'])) {
                            $activity->setMetadata('content', (string) $payload['object']['content']);
                        }

                        if (!empty($payload['object']['inReplyTo'])) {
                            $activity->setMetadata('reply', (string) $payload['object']['inReplyTo']);
                        }
                    }

                    if ((bool) $activity->preInboxSave($entity)) {
                        $activity->save();
                    } elseif ($payload['type'] !== 'Delete' && (bool) elgg_get_plugin_setting('log_general_inbox_error', 'activitypub')) {
                        $this->log(elgg_echo('activitypub:inbox:unsaved', [print_r($payload, true)]), 'log_general_inbox_error');
                    }
                });

                $status = 202;

                $response = new \Elgg\Http\OkResponse('', $status);
            } else {
                $status = 403;

                $response = new \Elgg\Http\ErrorResponse('', $status);

                if ((bool) elgg_get_plugin_setting('log_general_inbox_error', 'activitypub')) {
                    $this->log(elgg_echo('activitypub:inbox:general:blocked', [$actor]), 'log_general_inbox_error');
                }
            }

            $response->setHeaders([
                'Accept' => 'application/activity+json, application/ld+json; profile="https://www.w3.org/ns/activitystreams"',
                'Content-Type' => 'application/activity+json; charset=utf-8',
            ]);
        } catch (\Exception $e) {
            if ((bool) elgg_get_plugin_setting('log_general_inbox_error', 'activitypub')) {
                $this->log(elgg_echo('activitypub:inbox:general:error', [$e->getMessage(), (string) $request->getHttpRequest()->getContent()]), 'log_general_inbox_error');
            }
        }

        return $response;
    }

    /**
     * Returns if the actor is followed by the user.
     *
     * @param string $followee
     * @param int $guid
     *
     * @return bool
     */
    protected function isFollowee($followee, int $guid): bool
    {
        $count = elgg_call(ELGG_IGNORE_ACCESS, function () use ($followee, $guid) {
            return elgg_count_entities([
                'types' => 'object',
                'subtypes' => ActivityPubActivity::SUBTYPE,
                'owner_guid' => $guid,
                'metadata_name_value_pairs' => [
                    [
                        'name' => 'status',
                        'value' => 1,
                    ],
                    [
                        'name' => 'activity_object',
                        'value' => $followee,
                    ],
                    [
                        'name' => 'collection',
                        'value' => ActivityPubActivity::OUTBOX,
                    ],
                    [
                        'name' => 'activity_type',
                        'value' => ['Follow', 'Join'],
                    ],
                ],
            ]);
        });

        return $count === 1;
    }

    /**
     * Gets the object.
     *
     * @param $payload
     *
     * @return mixed|string
     */
    protected function getObject($payload)
    {
        $object = '';

        if (isset($payload['object'])) {
            if (is_array($payload['object']) && isset($payload['object']['object'])) {
                if ($payload['type'] === 'Accept' && isset($payload['object']['actor'])) {
                    $object = $payload['object']['actor'];
                } else {
                    $object = $payload['object']['object'];
                }
            } elseif (is_array($payload['object']) && isset($payload['object']['id'])) {
                $object = $payload['object']['id'];
            } elseif ($payload['type'] === 'Move' && !empty($payload['target'])) {
                $object = $payload['target'];
            } elseif (is_string($payload['object'])) {
                $object = $payload['object'];
            }
        }

        return $object;
    }

    /** Logger */
    public function log($message = '', $log_type = 'log_general_inbox_error')
    {
        if ($log_type === 'log_error_signature') {
            $log_file = elgg_get_data_path() . 'activitypub/logs/log_error_signature';
        } else {
            $log_file = elgg_get_data_path() . 'activitypub/logs/log_general_inbox_error';
        }

        $log = new Logger('ActivityPub');
        $log->pushHandler(new StreamHandler($log_file, Logger::WARNING));

        // add records to the log
        return $log->warning($message);
    }
}
