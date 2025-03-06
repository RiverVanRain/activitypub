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

class SharedInboxController {
	
	/**
	 * Shared Inbox routing callback.
	 */
	public function __invoke(\Elgg\Request $request): \Elgg\Http\Response {
		$status = 400;
		
		$response = new \Elgg\Http\ErrorResponse('', $status);

		if ($request->getMethod() !== 'POST') {
			return $response;
		}
	
		$payload = json_decode((string) $request->getHttpRequest()->getContent(), true);
		
		if (!isset($payload)) {
			if ((bool) elgg_get_plugin_setting('log_general_inbox_error', 'activitypub')) {
				$this->log(elgg_echo('activitypub:inbox:general:payload:empty', [(string) $request->getHttpRequest()->getContent()]), 'log_general_inbox_error');
			}
			
			return $response;
		}

		if (!JsonLdHelper::isSupportedContext($payload)) {          
			return null;
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
			if (!(bool) elgg()->activityPubUtility->domainIsGlobalBlocked($actor)) {
				$entity = elgg()->activityPubManager->getEntityFromUri($actor);
					
				if ($entity instanceof \Elgg\ActivityPub\Entity\FederatedUser && $this->isFollowee($entity)) {
					// The signature check is used to set the status of the activity.
					// There is a big chance some might fail depending how the request is
					// signed and which RFC version is used. In case the verification
					// fails, we rejects request.
					try {
						elgg()->activityPubSignature->verifySignature($request->getHttpRequest(), $actor, elgg()->activityPubUtility->getServer());
					} catch (\Exception $e) {
						if ((bool) elgg_get_plugin_setting('log_error_signature', 'activitypub')) {
							$this->log(elgg_echo('activitypub:inbox:signature:exception', [$e->getMessage()]), 'log_error_signature');
						}
					}

					// Get the object.
					$object = $this->getObject($payload);
						
					elgg_call(ELGG_IGNORE_ACCESS | ELGG_SHOW_DISABLED_ENTITIES, function () use ($entity, $id, &$payload, $actor, $object, $request) {
						$activity = new ActivityPubActivity();
						$activity->owner_guid = (int) $entity->guid;
						$activity->setMetadata('collection', ActivityPubActivity::INBOX);
						$activity->setMetadata('external_id', $id);
						$activity->setMetadata('activity_type', $payload['type']);
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
						} else if ((bool) elgg_get_plugin_setting('log_general_inbox_error', 'activitypub')) {
							$this->log(elgg_echo('activitypub:inbox:unsaved', [print_r($payload, true)]), 'log_general_inbox_error');
						}
					});

					$status = 202;
						
					$response = new \Elgg\Http\OkResponse('', $status);

					$response->setHeaders([
						'Accept' => 'application/activity+json, application/ld+json; profile="https://www.w3.org/ns/activitystreams"',
						'Content-Type' => 'application/activity+json; charset=utf-8',
					]);
				}
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
			
		/*
		// WIP
		$proccessCollectionService = elgg()->activityPubProcessCollection;
		$proccessCollectionService
			->withJson($payload)
			->withActor($actor)
			->process();

		return new JsonActivityResponse([]);
		*/
		
        return $response;
	}

	/**
     * Returns if the actor is followee or follower.
     *
     * @param ElggEntity $entity
     *
     * @return bool
     */
	protected function isFollowee($entity): bool {
		$count = elgg_call(ELGG_IGNORE_ACCESS, function () use ($entity) {
			return $entity->countEntitiesFromRelationship('remote_friend');
		});

		return $count > 0;
	}

	/**
     * Gets the object.
     *
     * @param $payload
     *
     * @return mixed|string
     */
	protected function getObject($payload) {
		$object = '';

		if (isset($payload['object'])) {
			if (is_array($payload['object']) && isset($payload['object']['object'])) {
				if ($payload['type'] === 'Accept' && isset($payload['object']['actor'])) {
					$object = $payload['object']['actor'];
				} else {
					$object = $payload['object']['object'];
				}
			} else if (is_array($payload['object']) && isset($payload['object']['id'])) {
				$object = $payload['object']['id'];
			} else if ($payload['type'] === 'Move' && !empty($payload['target'])) {
				$object = $payload['target'];
			} else if (is_string($payload['object'])) {
				$object = $payload['object'];
			}
		}

		return $object;
	}
	
	/** Logger */
	public function log($message = '', $log_type = 'log_general_inbox_error') {
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
