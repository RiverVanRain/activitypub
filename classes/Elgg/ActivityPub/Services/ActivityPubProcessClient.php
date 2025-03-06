<?php

namespace Elgg\ActivityPub\Services;

use Elgg\ActivityPub\Entity\ActivityPubActivity;
use Elgg\Traits\Di\ServiceFacade;
use GuzzleHttp\Exception\RequestException;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;


class ActivityPubProcessClient {
	use ServiceFacade;

	/**
	 * Returns registered service name
	 * @return string
	 */
	public static function name() {
		return 'activityPubProcessClient';
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function __get($name) {
		return $this->$name;
	}

	/**
	 * Prepare the outbox queue.
	 *
	 * Based on the host of the followers, a queue item will be created per host
	 * so that when the actual items are send out, we can more easily catch
	 * exceptions (e.g. host not found, timeouts etc.) and that other requests to
	 * different hosts don't fail.
	 *
	 * @param int $time_limit
	 * How long in seconds this method may run.
	 *
	 * @return mixed
	 */
	public function prepareOutboxQueue() {
		$activities = elgg_call(ELGG_IGNORE_ACCESS, function () {
			return elgg_get_entities([
				'types' => 'object',
				'subtypes' => ActivityPubActivity::SUBTYPE,
				'metadata_name_value_pairs' => [
					[
						'name' => 'queued',
						'value' => 1,
					],
					[
						'name' => 'processed',
						'value' => 0,
					],
					[
						'name' => 'collection',
						'value' => ActivityPubActivity::OUTBOX,
					],
				],
				'limit' => false,
				'batch' => true,
				'batch_size' => 50,
				'batch_inc_offset' => false,
			]);
		});
		
		if (!empty($activities)) {
			foreach($activities as $activity) {
				try {
					// Build activity
					$build = $activity->buildActivity();
	
					// Send to.
					$inboxes = [];
					$targets = [];
					$followers_url = $activity->getActor() . '/followers';
					$group_url = elgg_generate_url('view:activitypub:group', [
						'guid' => (int) $activity->owner_guid
					]);
			  
					if (!empty($build['to'])) {
						foreach ($build['to'] as $t) {
							if ($t != ActivityPubActivity::PUBLIC_URL && !in_array($t, [$followers_url, $group_url])) {
								$targets[] = $t;
							}
						}
					}
					
					if ((bool) elgg_get_plugin_setting('log_general_inbox_error', 'activitypub')) {
						$this->log("buildActivity:\n" . print_r($build, true));
					}
	
					// Add followers.
					if ($this->notifyFollowers($build, $followers_url)) {
						// Get followers.
						$followers = elgg_call(ELGG_IGNORE_ACCESS, function () use ($activity) {
							return elgg_get_entities([
								'types' => 'object',
								'subtypes' => ActivityPubActivity::SUBTYPE,
								'metadata_name_value_pairs' => [
									[
										'name' => 'status',
										'value' => 1,
									],
									[
										'name' => 'processed',
										'value' => 1,
									],
									[
										'name' => 'activity_type',
										'value' => ['Follow', 'Join'],
									],
									[
										'name' => 'activity_object',
										'value' => $activity->getActor(),
									],
								],
								'limit' => 0,
								'batch' => true,
								'batch_size' => 50,
								'batch_inc_offset' => false,
							]);
						});
						
						foreach ($followers as $follower) {
							$targets[] = $follower->getActor();
						}
					}
					
					// Create inboxes based on host.
					foreach ($targets as $target) {
						$parsed = parse_url($target);
						$inboxes[$parsed['host']][] = $target;
					}
	
					if ((bool) elgg_get_plugin_setting('log_general_inbox_error', 'activitypub')) {
						$this->log("inboxes:\n" . print_r($inboxes, true));
					}
			  
					if (!empty($inboxes)) {
						foreach ($inboxes as $targets) {
							$this->handleOutboxQueue($activity, $build, $targets);
						}
					}
				} catch (\Exception $e) {
					if ((bool) elgg_get_plugin_setting('log_general_inbox_error', 'activitypub')) {
						$this->log("Outbox prepare general exception in {$e->getFile()} line {$e->getLine()} for {$activity->id()}: {$e->getMessage()}");
					}
				}
				
				try {
					$activity->setMetadata('queued', 0);
					$activity->setMetadata('processed', 1);
					$activity->setMetadata('status', 1);
					
					// Scheduler integration.
					if (!empty((string) $activity->entity_subtype) && !empty((int) $activity->entity_guid)) {
						$entity = get_entity((int) $activity->entity_guid);
						
						if ($entity instanceof \ElggEntity && (bool) $entity->scheduling_enable && !(bool) $entity->scheduling_prevent) {
							$activity->setMetadata('status', 0);
						}
					}
					
					$activity->save();
					$activity->postOutboxProcess();
				} catch (\Exception $ignored) {}
			} 
		}
	}

	/**
	 * Handles the Outbox queue.
	 *
	 * @param \Elgg\ActivityPub\Entity\ActivityPubActivity $activityPubActivity
	 *   The activity.
	 * @param $build
	 *   The build for this activity.
	 * @param $targets
     *   The targets to send to.
	 */
	public function handleOutboxQueue(ActivityPubActivity $activity, array $build = [], array $targets = []) {
		$server = elgg()->activityPubUtility->getServer();

		$inboxes = [];
		
		$build['@context'] = ActivityPubActivity::CONTEXT_URL;
		
		try {
			$actor = $activity->getOwnerEntity();
			// Get inboxes.
			foreach ($targets as $target) {
				$target_actor = null;
				try {
					$target_actor = $server->actor($target);
				} catch (\Exception $ignored) {}
				
				if ($target_actor) {
					$inbox = $target_actor->get('inbox');
					
					if ($activity->canUseSharedInbox() && ($endpoints = $target_actor->get('endpoints')) && !empty($endpoints['sharedInbox'])) {
						$inbox = $endpoints['sharedInbox'];
					}
					
					if (is_string($inbox)) {
						$inboxes[$inbox] = $inbox;
					}
				}
			}

			if (!empty($inboxes)) {
				$keyId = $activity->getActor();

				// Create digest.
				$json = json_encode($build, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
				$digest = elgg()->activityPubSignature->createDigest($json);

				foreach ($inboxes as $inbox) {
					$parsed = parse_url($inbox);
					$host = $parsed['host'];
					$path = $parsed['path'];
					$date = gmdate('D, d M Y H:i:s T', time());
					
					// Create signature.
					if ($actor instanceof \ElggUser) {
						$name = (string) $actor->username;
					} else if ($actor instanceof \ElggGroup) {
						$name = (string) $actor->activitypub_groupname;
					}
					
					$signature = elgg()->activityPubSignature->createSignature($name, $host, $path, $digest, $date);
					
					// WIP - it doesn't seem to work yet - 
					/*
					// FEP-8fcf: Followers collection synchronization across servers
					if ($actor instanceof \ElggUser) {
						$collectionId = elgg_generate_url('view:activitypub:user:followers', [
							'guid' => (int) $actor->guid
						]);
						$url = elgg_generate_url('activitypub:user:followers:synchronization', [
							'guid' => (int) $actor->guid
						]);
					} else if ($actor instanceof \ElggGroup) {
						$collectionId = elgg_generate_url('view:activitypub:group:followers', [
							'guid' => (int) $actor->guid
						]);
						$url = elgg_generate_url('activitypub:group:followers:synchronization', [
							'guid' => (int) $actor->guid
						]);
					}
					
					$followers = elgg()->activityPubUtility->getFollowersIds($actor);
					
					$synchronization = '';
					foreach ($followers as $follower) {
						$hash = hash('sha256', $follower);
						$synchronization .= hex2bin($hash);
					}

					$synchronization = bin2hex($synchronization);
					*/

					$headers = [
						'Accept' => 'application/activity+json, application/ld+json; profile="https://www.w3.org/ns/activitystreams"',
						'Content-Type' => 'application/activity+json; charset=utf-8',
						'Host' => $host,
						'Date' => $date,
						'Digest' => $digest,
						'Signature' => 'keyId="' . $keyId . '#main-key",headers="(request-target) host date digest",signature="' . base64_encode($signature) . '",algorithm="rsa-sha256"',
						//'Collection-Synchronization' => 'collectionId="' . $collectionId . '", url="' . $url . '", digest="' . $synchronization . '"',
					];

					try {
						$response = elgg()->activityPubUtility->http_client()->post($inbox, ['body' => $json, 'headers' => $headers]);
						$code = $response->getStatusCode();
						$body = (string) $response->getBody();
						
						if ((bool) elgg_get_plugin_setting('log_general_inbox_error', 'activitypub')) {
							$this->log("Outbox response to {$inbox} for {$activity->guid}: code: {$code} - {$body}");
						}
					} catch (RequestException $e) {
						if ((bool) elgg_get_plugin_setting('log_general_inbox_error', 'activitypub')) {
							$this->log("Outbox exception to {$inbox} for {$activity->guid} to {$inbox}: {$e->getMessage()}");
						}
					}
				}
			}
        } catch (\Exception $e) {
			if ((bool) elgg_get_plugin_setting('log_general_inbox_error', 'activitypub')) {
				$this->log("Outbox general exception in {$e->getFile()} line {$e->getLine()} for {$activity->guid}: {$e->getMessage()}");
			}
        }
	}

	/**
	 * Handles the Inbox queue.
     *
     * @param int $time_limit  How long in seconds this command may run.
     *
     */
	public function handleInboxQueue() {
		$activities = elgg_call(ELGG_IGNORE_ACCESS, function () {
			return elgg_get_entities([
				'types' => 'object',
				'subtypes' => ActivityPubActivity::SUBTYPE,
				'metadata_name_value_pairs' => [
					[
						'name' => 'queued',
						'value' => 1,
					],
					[
						'name' => 'processed',
						'value' => 0,
					],
					[
						'name' => 'collection',
						'value' => ActivityPubActivity::INBOX,
					],
				],
				'limit' => false,
				'batch' => true,
				'batch_size' => 50,
				'batch_inc_offset' => false,
			]);
		});
		
		if (!empty($activities)) {
			foreach($activities as $activity) {
				try {
					$activity->doInboxProcess();
				} catch (\Exception $e) {
					if ((bool) elgg_get_plugin_setting('log_general_inbox_error', 'activitypub')) {
						$this->log(elgg_echo('activitypub:inbox:general:exception', [$e->getFile(), $e->getLine(), $activity->guid, $e->getMessage()]));
					}
				}
				
				try {
					$activity->setMetadata('queued', 0);
					$activity->setMetadata('processed', 1);
					$activity->setMetadata('status', 1);
					
					$activity->save();
					$activity->postSaveProcess();
				} catch (\Exception $ignored) {}
			}
		}
	}
	
	/**
	 * Notify followers.
	 *
	 * @param $build
	 * @param $followers_url
	 *
	 * @return false
	 */
	protected function notifyFollowers(array $build = [], $followers_url) {
		$return = false;

		if ((isset($build['to']) && in_array($followers_url, $build['to'])) || (isset($build['cc']) && in_array($followers_url, $build['cc']))) {
			$return = true;
		}

		return $return;
	}
	
	/** Logger */
	public function log($message = '') {
		$log_file = elgg_get_data_path() . 'activitypub/logs/log_general_inbox_error';
		
		$log = new Logger('ActivityPub');
		$log->pushHandler(new StreamHandler($log_file, Logger::WARNING));

		// add records to the log
		return $log->warning($message);
	}
	
}
