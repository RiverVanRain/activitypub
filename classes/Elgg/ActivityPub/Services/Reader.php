<?php

namespace Elgg\ActivityPub\Services;

use ElggComment;
use ElggObject;
use Elgg\Values;
use Elgg\ActivityPub\Entity\ActivityPubActivity;
use Elgg\ActivityPub\Entity\FederatedObject;
use Elgg\ActivityPub\Entity\FederatedUser;
use Elgg\ActivityPub\Enums\FederatedEntitySourcesEnum;
use Elgg\ActivityPub\Types\Core\ActivityType;
use Elgg\Traits\Di\ServiceFacade;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class Reader {

	use ServiceFacade;

	/**
	 * Returns registered service name
	 * @return string
	 */
	public static function name() {
		return 'activityPubReader';
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function __get($name) {
		return $this->$name;
	}

	/**
	 * Import from cron job
	 */
	public function import($feed_url) {
		if (empty($feed_url)) {
			return;
		}

		// get outbox link
		$outbox_url = \Elgg\ActivityPub\Services\ResolveService::getRemoteObject($feed_url);
		
		if (!$outbox_url || !isset($outbox_url['outbox'])) {
			if ((bool) elgg_get_plugin_setting('log_general_inbox_error', 'activitypub')) {
				$this->log(elgg_echo('activitypub:outbox:import_feed:error:outbox_url', [$outbox_url]));
			}
			return;
		}

		$feed_url = (string) $outbox_url['outbox'];

		$response = elgg()->activityPubClient->request('GET', $feed_url);
		$data = json_decode($response->getBody()->getContents(), true);

		if (!isset($data['orderedItems'])) {
			if (!isset($data['first'])) {
				if ((bool) elgg_get_plugin_setting('log_general_inbox_error', 'activitypub')) {
					$this->log(elgg_echo('activitypub:outbox:import_feed:error:first_page', [$feed_url]));
				}
				return;
			}

			$first = $data['first'];
			if (is_array($data['first'])) {
				$first = $data['first']['id'];
			}

			$response = elgg()->activityPubClient->request('GET', $first);
			
			$outbox = json_decode($response->getBody()->getContents(), true);

			$data = $outbox;
		}

		$items = [];
		
		foreach ($data['orderedItems'] as $payload) {
			// WIP - handle for more types
			if ($payload['type'] === 'Create') {
				if (is_array($payload['object'])) {
					$item = [
						'id' => (string) $payload['object']['id'],
						'published' => (string) $payload['object']['published'],
						'author' => $payload['object']['attributedTo'],
					];

					$canonical_url = (string) $payload['object']['id'];

					if (isset($payload['object']['url'])) {
						$canonical_url = (string) $payload['object']['url'];
					}

					$item['canonical_url'] = $canonical_url;
					
					if (!empty($payload['object']['content'])) {
						$item['content'] = (string) $payload['object']['content'];
					}

					if (!empty($payload['object']['summary'])) {
						$item['summary'] = (string) $payload['object']['summary'];
					}

					if (!empty($payload['object']['inReplyTo'])) {
						$item['reply'] = $payload['object']['inReplyTo'];
					}

					if (!empty($payload['object']['tag'])) {
						$item['tag'] = $payload['object']['tag'];
					}

					if (!empty($payload['object']['attachment'])) {
						$item['attachments'] = $payload['object']['attachment'];
					}

					$items[] = $item;
				}
			}
		}

		// import items
		$canonicalUrls = array_column($items, 'canonical_url');
		$permalinks = $this->findNewPermalinks($items, $canonicalUrls);
		if (empty($permalinks)) {
			// everythink already imported
			if ((bool) elgg_get_plugin_setting('log_general_inbox_error', 'activitypub')) {
				$this->log(elgg_echo('activitypub:outbox:import_feed:created', [0, $feed_url]));
			}

			return true;
		}

		$count = 0;

		foreach ($permalinks as $item) {	
			if ($this->createFederatedObject($item)) {
				$count++;
			}
		}

		if ((bool) elgg_get_plugin_setting('log_general_inbox_error', 'activitypub')) {
			$this->log(elgg_echo('activitypub:outbox:import_feed:created', [$count, $feed_url]));
		}

		return true;
	}

	/**
	 * Read from payload
	 */
	public function read(array $payload = []) {
		if (empty($payload)) {
			return;
		}
		
		$items = [];
		
		if ($payload['type'] === 'Create') {
			if (is_array($payload['object'])) {
				$item = [
					'id' => (string) $payload['object']['id'],
					'published' => (string) $payload['object']['published'],
					'author' => $payload['object']['attributedTo'],
				];

				$canonical_url = (string) $payload['object']['id'];

				if (isset($payload['object']['url'])) {
					$canonical_url = (string) $payload['object']['url'];
				}

				$item['canonical_url'] = $canonical_url;
					
				if (!empty($payload['object']['content'])) {
					$item['content'] = (string) $payload['object']['content'];
				}

				if (!empty($payload['object']['summary'])) {
					$item['summary'] = (string) $payload['object']['summary'];
				}

				if (!empty($payload['object']['inReplyTo'])) {
					$item['reply'] = $payload['object']['inReplyTo'];
				}

				if (!empty($payload['object']['tag'])) {
					$item['tag'] = $payload['object']['tag'];
				}

				if (!empty($payload['object']['attachment'])) {
					$item['attachments'] = $payload['object']['attachment'];
				}

				$items[] = $item;
			}
		}
		
		// import items
		$canonicalUrls = array_column($items, 'canonical_url');
		$permalinks = $this->findNewPermalinks($items, $canonicalUrls);
		if (empty($permalinks)) {
			// everythink already imported
			if ((bool) elgg_get_plugin_setting('log_general_inbox_error', 'activitypub')) {
				$this->log(elgg_echo('activitypub:outbox:import_feed:created', [0, $feed_url]));
			}

			return true;
		}

		$count = 0;

		foreach ($permalinks as $item) {	
			if ($this->createFederatedObject($item)) {
				$count++;
			}
		}

		if ((bool) elgg_get_plugin_setting('log_general_inbox_error', 'activitypub')) {
			$this->log(elgg_echo('activitypub:outbox:import_feed:created', [$count, $feed_url]));
		}

		return true;
	}
	
	/**
	 * Filter out the items which have already been imported
	 *
	 * @param array $items          items from feed
	 * @param array $canonicalUrls  canonical_url metadata from items
	 *
	 * @return string[]
	 */
	protected function findNewPermalinks(array $items, array $canonicalUrls) {
		$already_imported = elgg_call(ELGG_IGNORE_ACCESS, function() use ($canonicalUrls) {
			return elgg_get_entities([
				'type' => 'object',
				'subtype' => [FederatedObject::SUBTYPE, 'comment'],
				'metadata_name_value_pairs' => [
					[
						'name' => 'canonical_url',
						'value' => $canonicalUrls,
					],
				],
				'selects' => [
					function (\Elgg\Database\QueryBuilder $qb, $main_alias) {
						$join_alias = $qb->joinMetadataTable($main_alias, 'guid', 'canonical_url', 'inner', 'feed');
						return "{$join_alias}.value AS canonical_url";
					},
				],
				'limit' => false,
				'batch' => true,
				'batch_size' => 50,
				'batch_inc_offset' => false,
				'callback' => function ($row) {
					return [
						'guid' => (int) $row->guid,
						'canonical_url' => $row->canonical_url,
					];
				}
			]);
		});
		
		if (empty($already_imported)) {
			return $items;
		}
		
		$keys = [];
		foreach ($already_imported as $row) {
			$key = $row['canonical_url'];
			$keys[] = $key;
		}

		$filtered_items = array_filter($items, function($item) use ($keys) {
			return !in_array($item['canonical_url'], $keys);
		});
		
		return array_values($filtered_items);
	}

	/**
	 * Search the existed object to create a comment
	 *
	 * @param string $item feed item
	 *
	 * @return FederatedObject|ElggComment|null
	 */
	protected function searchFederatedObjects($item) {
		$objects = elgg_call(ELGG_IGNORE_ACCESS, function() use ($item) {
			return elgg_get_entities([
				'type' => 'object',
				'subtype' => [FederatedObject::SUBTYPE, 'comment'],
				'metadata_name_value_pairs' => [
					[
						'name' => 'external_id',
						'value' => $item['reply'],
					],
				],
				'limit' => 1,
			]);
		});

		if (empty($objects)) {
			return null;
		}
		
		foreach ($objects as $object) {
			return $object;
		}
	}

	/**
	 * Search the existed object to create a post
	 *
	 * @param string $item feed item
	 *
	 * @return \ElggWire|\wZm\River\Entity\River|null
	 */
	protected function searchElggObjects($item) {
		$objects = elgg_call(ELGG_IGNORE_ACCESS, function() use ($item) {
			return elgg_get_entities([
				'type' => 'object',
				'subtype' => ['thewire', 'river'],
				'metadata_name_value_pairs' => [
					[
						'name' => 'external_id',
						'value' => (string) $item['id'],
					],
				],
				'limit' => 1,
			]);
		});

		if (empty($objects)) {
			return null;
		}
		
		foreach ($objects as $object) {
			return $object;
		}
	}
	
	/**
	 * Create a FederatedObject based on the feed item
	 *
	 * @param string $item feed item
	 *
	 * @return bool
	 */
	protected function createFederatedObject($item) {
		$user = elgg()->activityPubManager->getEntityFromUri($item['author']);
		if (!$user instanceof FederatedUser || $user->isBanned()) {
			return;
		}

		$created = Values::normalizeTimestamp((string) $item['published']);
		
		if ((int) $created < (int) $user->time_created) {
			return;
		}

		// try create a group post
		if (!empty($item['tag'])) {
			$tags = $item['tag'];

			foreach ($tags as $tag) {
				if (!elgg()->activityPubManager->isLocalUri($tag['href'])) {
					continue;
				}
				
				$group = elgg()->activityPubManager->getEntityFromLocalUri($tag['href']);

				if ($group instanceof \ElggGroup) {
					// Create TheWire
					if (elgg_is_active_plugin('thewire_tools') && (bool) elgg_get_plugin_setting('enable_group', 'thewire_tools') && $group->isToolEnabled('thewire')) {
						return elgg_call(ELGG_IGNORE_ACCESS, function () use ($user, $group, $item) {
							$this->handleWirePostCreation($user, $group, $item);
						});
					}

					// Create Reaction
					else if (elgg_is_active_plugin('river') && (bool) elgg_get_plugin_setting('enable_group', 'river') && $group->isToolEnabled('river')) {
						return elgg_call(ELGG_IGNORE_ACCESS, function () use ($user, $group, $item) {
							$this->handleReactionCreation($user, $group, $item);
						});
					}
				}
			}
		}

		// try create a comment
		if (!empty($item['reply'])) {
			// remote reply on local content
			if ((bool) elgg()->activityPubManager->isLocalUri($item['reply'])) {
				$entity_guid = activitypub_get_guid($item['reply']);
				$entity = get_entity($entity_guid);
				
				if ($entity instanceof \ElggObject && $entity->canComment() && (bool) elgg_get_plugin_setting('can_activitypub:object:comment', 'activitypub')) {
					return $this->createElggComment($item, $entity);
				}
			}
			
			// remote reply on remote content
			$object = $this->searchFederatedObjects($item);

			if ($object instanceof FederatedObject || $object instanceof ElggComment) {
				return $this->createElggComment($item, $object);
			}
		}

		// WIP for wZm\TopicPost
		
		// fake login
		$session = _elgg_services()->session_manager;
		$backup_user = $session->getLoggedInUser();
		$session->setLoggedInUser($user);
		
		// create an object
		$object = new FederatedObject();
		$object->owner_guid = (int) $user->guid;
		$object->access_id = ACCESS_PUBLIC;
		
		$object->time_created = $created;
		$object->external_id = (string) $item['id'];
		$object->canonical_url = (string) $item['canonical_url'];

		$object->title = elgg_sanitize_input(elgg_echo('activitypub:post:federated', [(string) $user->getDisplayName()]));

		$summary = null;
		
		if (!empty($item['summary'])) {
			$summary = (string) $item['summary'];
		}

		$content = null;
		
		if (!empty($item['content'])) {
			$content = $summary = (string) $item['content'];
		}

		if (is_callable('mb_convert_encoding')) {
			$excerpt = mb_convert_encoding($summary, 'HTML-ENTITIES', 'UTF-8');
			$description = mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8');
		} else {
			$excerpt = $summary;
			$description = $content;
		}
		
		$object->excerpt = elgg_sanitize_input($excerpt);
		$object->description = elgg_sanitize_input($description);
		
		$object->status = 'published';
		$object->published_status = 'published';
		
		if (!empty($item['reply'])) {
			$object->reply_on = $item['reply'];
		}
		
		$object->source = FederatedEntitySourcesEnum::ACTIVITY_PUB;
		
		if (!empty($item['attachments'])) {
			$description = (string) $object->description;
			
			foreach ($item['attachments'] as $attachment) {
				if (!isset($attachment['type']) || !isset($attachment['url'])) {
					continue;
				}
				if (!in_array($attachment['type'], ['Audio', 'Document', 'Image', 'Video'], true)) {
					continue;
				}

				$title = (string) $attachment['url'];
				
				if (!empty($attachment['name'])) {
					$title = (string) $attachment['name'];
				}
				
				$description .= elgg_view('activitypub/object/attachments', [
					'attachments' => [
						'type' => (string) $attachment['type'],
						'mediaType' => !empty($attachment['mediaType']) ? (string) $attachment['mediaType'] : null,
						'url' => (string) $attachment['url'],
						'title' => $title,
						'width' => !empty($attachment['width']) ? (string) $attachment['width'] : null,
						'height' => !empty($attachment['height']) ? (string) $attachment['height'] : null,
					]
				]);
			}
			
			$object->description = (string) $description;
		}
		
		// save object
		if (!(bool) $object->save()) {
			return false;
		}
		
		// let others know about the import
		elgg_trigger_event_results('import', 'activitypub', [
			'item' => $item,
			'entity' => $object,
		], true);
		
		elgg_create_river_item([
			'view' => 'river/object/federated/create',
			'action_type' => 'create',
			'subject_guid' => (int) $object->owner_guid,
			'object_guid' => (int) $object->guid,
			'target_guid' => (int) $object->container_guid,
			'posted' => (int) $object->time_created,
		]);

		elgg_trigger_event('publish', 'object', $object);

		// restore login
		if ($backup_user instanceof \ElggUser) {
			$session->setLoggedInUser($backup_user);
		} else {
			$session->removeLoggedInUser();
		}
		
		return true;
	}
	
	/**
	 * Create ElggComment based on the feed item and object
	 *
	 * @param string $item feed item
	 * @param FederatedObject|ElggObject $object
	 *
	 * @return bool
	 */
	protected function createElggComment($item, ElggObject $object) {
		$user = elgg()->activityPubManager->getEntityFromUri($item['author']);
		if (!$user instanceof FederatedUser || $user->isBanned()) {
			return;
		}

		$created = Values::normalizeTimestamp((string) $item['published']);
		
		if ((int) $created < (int) $user->time_created) {
			return;
		}

		// fake login
		$session = _elgg_services()->session_manager;
		$backup_user = $session->getLoggedInUser();
		$session->setLoggedInUser($user);
		
		// create a comment
		$comment = new ElggComment();
		$comment->owner_guid = (int) $user->guid;
		$comment->container_guid = (int) $object->guid;
		$comment->access_id = (int) $object->access_id;
		$comment->time_created = (int) $created;
		$comment->external_id = (string) $item['id'];
		$comment->canonical_url = (string) $item['canonical_url'];
		$comment->title = elgg_sanitize_input(elgg_echo('activitypub:reply:by', [(string) $user->getDisplayName()]));

		$summary = null;
		
		if (!empty($item['summary'])) {
			$summary = (string) $item['summary'];
		}

		$content = null;
		
		if (!empty($item['content'])) {
			$content = $summary = (string) $item['content'];
		}

		if (is_callable('mb_convert_encoding')) {
			$excerpt = mb_convert_encoding($summary, 'HTML-ENTITIES', 'UTF-8');
			$description = mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8');
		} else {
			$excerpt = $summary;
			$description = $content;
		}
		
		$comment->excerpt = elgg_sanitize_input($excerpt);
		$comment->description = elgg_sanitize_input($description);
		
		$comment->status = 'published';
		$comment->published_status = 'published';
		
		if (!empty($item['reply'])) {
			$comment->reply_on = $item['reply'];
		}
		
		if ($object instanceof ElggComment) {
			$comment->level = $object->getLevel() + 1;
			$comment->parent_guid = (int) $object->guid;
			$comment->thread_guid = (int) $object->getThreadGUID();
				
			// make sure comment is contained in the content
			$object = $object->getContainerEntity();
		}

		if (!empty($item['attachments'])) {
			$description = (string) $comment->description;
			
			foreach ($item['attachments'] as $attachment) {
				if (!isset($attachment['type']) || !isset($attachment['url'])) {
					continue;
				}
				if (!in_array($attachment['type'], ['Audio', 'Document', 'Image', 'Video'], true)) {
					continue;
				}

				$title = (string) $attachment['url'];
				
				if (!empty($attachment['name'])) {
					$title = (string) $attachment['name'];
				}
				
				$description .= elgg_view('activitypub/object/attachments', [
					'attachments' => [
						'type' => (string) $attachment['type'],
						'mediaType' => !empty($attachment['mediaType']) ? (string) $attachment['mediaType'] : null,
						'url' => (string) $attachment['url'],
						'title' => $title,
						'width' => !empty($attachment['width']) ? (string) $attachment['width'] : null,
						'height' => !empty($attachment['height']) ? (string) $attachment['height'] : null,
					]
				]);
			}
			
			$comment->description = (string) $description;
		}

		// save comment
		if (!(bool) $comment->save()) {
			return false;
		}

		// let others know about the import
		elgg_trigger_event_results('import', 'activitypub', [
			'item' => $item,
			'entity' => $comment,
		], true);
		
		elgg_create_river_item([
			'view' => 'river/object/comment/create',
			'action_type' => 'comment',
			'object_guid' => (int) $comment->guid,
			'target_guid' => (int) $object->guid,
		]);

		elgg_trigger_event('publish', 'object', $comment);
		
		// restore login
		if ($backup_user instanceof \ElggUser) {
			$session->setLoggedInUser($backup_user);
		} else {
			$session->removeLoggedInUser();
		}
		
		return true;
	}

	/**
	 * Handle thewire post creation.
	 *
	 */
	protected function handleWirePostCreation(\ElggUser $user, \ElggGroup $group, $item): bool {
		// the existed content
		$object = $this->searchElggObjects($item);

		if ($object instanceof \ElggWire) {
			return false;
		}
		
		if (empty($item['content'])) {
			return false;
		}

		if (!$group->isMember($user)) {
			return false;
		}
		
		// fake login
		$session = _elgg_services()->session_manager;
		$backup_user = $session->getLoggedInUser();
		$session->setLoggedInUser($user);
		
		// create an object
		$access_id = (int) $group->access_id;
		$parent_guid = 0;

		//reply
		if (!empty($item['reply'])) {
			$reply_uri = $item['reply'];
			$objects = elgg_call(ELGG_IGNORE_ACCESS, function() use ($reply_uri) {
				return elgg_get_entities([
					'type' => 'object',
					'subtype' => 'thewire',
					'metadata_name_value_pairs' => [
						[
							'name' => 'external_id',
							'value' => $reply_uri,
						],
					],
					'limit' => 1,
				]);
			});

			if (!empty($objects)) {
				foreach ($objects as $reply) {
					$access_id = (int) $reply->access_id;
					$parent_guid = (int) $reply->guid;
				}
			}
		}

		$content = (string) $item['content'];

		if (is_callable('mb_convert_encoding')) {
			$description = mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8');
		} else {
			$description = $content;
		}

		$guid = thewire_tools_save_post($description, (int) $user->guid, $access_id, $parent_guid, 'fediverse', 0, (int) $group->guid);

		$object = get_entity((int) $guid);

		// let others know about the import
		elgg_trigger_event_results('import', 'activitypub', [
			'item' => $item,
			'entity' => $object,
		], true);

		// restore login
		if ($backup_user instanceof \ElggUser) {
			$session->setLoggedInUser($backup_user);
		} else {
			$session->removeLoggedInUser();
		}
		
		return true;
	}

	/**
	 * Handle reaction creation.
	 *
	 */
	protected function handleReactionCreation(\ElggUser $user, \ElggGroup $group, $item): bool {
		// the existed content
		$object = $this->searchElggObjects($item);

		if ($object instanceof \wZm\River\Entity\River) {
			return false;
		}

		if (empty($item['content'])) {
			return false;
		}

		if (!$group->isMember($user)) {
			return false;
		}
		
		// fake login
		$session = _elgg_services()->session_manager;
		$backup_user = $session->getLoggedInUser();
		$session->setLoggedInUser($user);
		
		// create an object
		$access_id = (int) $group->access_id;
		$parent_guid = 0;

		//reply
		if (!empty($item['reply'])) {
			$reply_uri = $item['reply'];
			$objects = elgg_call(ELGG_IGNORE_ACCESS, function() use ($reply_uri) {
				return elgg_get_entities([
					'type' => 'object',
					'subtype' => 'river',
					'metadata_name_value_pairs' => [
						[
							'name' => 'external_id',
							'value' => $reply_uri,
						],
					],
					'limit' => 1,
				]);
			});

			if (!empty($objects)) {
				foreach ($objects as $reply) {
					$access_id = (int) $reply->access_id;
					$parent_guid = (int) $reply->guid;
				}
			}
		}

		$content = (string) $item['content'];

		if (is_callable('mb_convert_encoding')) {
			$description = mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8');
		} else {
			$description = $content;
		}

		$response = elgg()->river->savePost($description , (int) $user->guid, (int) $group->guid, $access_id, [
			'parent_guid' => $parent_guid,
			'source' => (string) $item['canonical_url'],
			'method' => 'fediverse',
		]);

		$data = $response->getContent();

		$object = get_entity((int) $data['post_guid']);

		// let others know about the import
		elgg_trigger_event_results('import', 'activitypub', [
			'item' => $item,
			'entity' => $object,
		], true);
		
		// restore login
		if ($backup_user instanceof \ElggUser) {
			$session->setLoggedInUser($backup_user);
		} else {
			$session->removeLoggedInUser();
		}
		
		return true;
	}

	// WIP
	
	/**
     * Emits the activity to the correct audience
     */
    public function emitActivity(ActivityType $activity, \ElggUser|\ElggGroup $actor): void {
        // Find a list of all our followers IDs
        foreach (elgg()->activityPubManager->getFollowersIds($actor) as $url) {
            $inboxUrl = $url . '/inbox';

			$this->postRequest($inboxUrl, $activity, $actor);
        }


        // If there are any mentions or additional cc's, also send to those
        foreach ($activity->object->cc as $cc) {
            try {
                $ccActor = $this->actorFactory->fromUri($cc);
            } catch (\Exception $e) {
                continue;
            }

            $this->postRequest($ccActor->inbox, $activity, $actor);
        }
    }

	private function postRequest(string $inboxUrl, ActivityType $activity, \ElggUser|\ElggGroup|\ElggSite $actor = null): bool {
        if (strpos($inboxUrl, elgg()->activityPubManager->getBaseUrl(), 0) === 0) {
            return false;
        }

        try {
			$privateKey = false;

			if ($actor instanceof \ElggUser) {
				$privateKey = elgg()->activityPubSignature->getPrivateKey($actor->username);
			} else if ($actor instanceof \ElggGroup) {
				$privateKey = elgg()->activityPubSignature->getPrivateKey($actor->activitypub_groupname);
			} else if ($actor instanceof \ElggSite) {
				$privateKey = elgg()->activityPubSignature->getPrivateKey((string) elgg_get_site_entity()->getDomain());
			}

			if ($privateKey) {
				$response = elgg()->activityPubClient
					->withPrivateKeys([
						elgg()->activityPubUtility->getActivityPubID($actor) . '#main-key' => (string) $privateKey,
					])
					->request('POST', $inboxUrl, [
						...$activity->getContextExport(),
						...$activity->export()
					]);
			}
     
			return true;
        } catch (\Exception $e) {
            $this->logger->info("POST $inboxUrl: Failed {$e->getMessage()}");
            return false;
        }
    }
	
	/**
	 * Get the actor for the current user.
	 *
	 * @return bool|NULL|\ElggUser
	 */
	protected function getActor() {
		if ((bool) elgg_get_logged_in_user_entity()->getPluginSetting('activitypub', 'enable_activitypub') && (bool) elgg_get_logged_in_user_entity()->activitypub_actor) {
			return elgg_get_logged_in_user_entity();
		}

		return false;
	}

	/**
	 * Get followees.
	 *
	 * @return array
	 *
	*/
	public function getFollowees() {
		static $loaded = false;
		static $items = [];

		if (!$loaded) {
			$actor = $this->getActor();
			
			$options = [
				'type' => 'object',
				'subtype' => ActivityPubActivity::SUBTYPE,
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
						'name' => 'actor',
						'value' => elgg_generate_url('view:activitypub:user', [
							'guid' => (int) $actor->guid,
						]),
					],
					[
						'name' => 'collection',
						'value' => ActivityPubActivity::OUTBOX,
					],
					[
						'name' => 'activity_type',
						'value' => 'Follow',
					],
				],
				'limit' => false,
				'batch' => true,
				'batch_size' => 50,
				'batch_inc_offset' => false,
			];
			
			$records = elgg_call(ELGG_IGNORE_ACCESS, function () use ($options) {
				return elgg_get_entities($options);
			});
			
			foreach ($records as $record) {
				$items[$record->mute][$record->object] = $record->object;
			}
		}

		return $items;
	}

	/**
	 * Build a Microsub item from an activity payload.
	 *
	 * @param Elgg\ActivityPub\Entity\ActivityPubActivity $activity
	 *
	 * @return \stdClass $item
	 */
	public function buildMicrosubItem(ActivityPubActivity $activity) {
		$payload = json_decode($activity->getPayload() ?: '', true);
		$context = json_decode($activity->getContext() ?: '', true);

		$item = [];
		$item['_id'] = $activity->guid;
		$item['type'] = 'entry';
		if ($activity->isPrivate()) {
			$item['url'] = !empty($payload['object']['id']) ? $payload['object']['id'] : str_replace('/activity', '', $activity->getExternalId());
		} else {
			$item['url'] = $activity->getActivityObject();
		}
		$item['published'] = date('c', (int) $activity->time_created);

		// Content and response type.
		if ($activity->getActivityType() === 'Like') {
			$item['like-of'] = [$activity->getActivityObject()];
		} else if ($activity->getActivityType() === 'Announce') {
			$item['repost-of'] = [$activity->getActivityObject()];
		} else if ($activity->getActivityType() === 'Follow') {
			$item['content'] = (object) ['html' => 'This person is now following you!'];
		} else if ($activity->getActivityType() === 'Create') {
			if (!empty($payload['object']) && is_array($payload['object'])) {
				if (!empty($payload['object']['inReplyTo'])) {
					$item['in-reply-to'] = [$payload['object']['inReplyTo']];
				}

				if (!empty($payload['object']['name'])) {
					$item['name'] = $payload['object']['name'];
				}

				if (!empty($payload['object']['content'])) {
					$item['content'] = (object) ['html' => $payload['object']['content']];
				}

				if (!empty($payload['object']['attachment'])) {
					$this->handleAttachments($payload['object']['attachment'], $item);
				}
			} else {
				if (!empty($payload['name'])) {
					$item['name'] = $payload['name'];
				}  else {
					// currently treat as repost
					$item['repost-of'] = [$activity->getActivityObject()];
				}
			}
		}

		// Context.
		if (!empty($context) && (isset($item['repost-of']) || isset($item['like-of']) || isset($item['in-reply-to']))) {
			$url = null;
			if (isset($item['repost-of'])) {
				$url = $item['repost-of'][0];
			} else if (isset($item['like-of'])) {
				$url = $item['like-of'][0];
			} else if (isset($item['in-reply-to'])) {
				$url = $item['in-reply-to'][0];
			}

			if (isset($url) && isset($context['id']) && $context['id'] === $url && empty($activity->getTargetEntityGuid())) {
				$ref = [];
				if (!empty($context['name'])) {
					$ref['name'] = $context['name'];
				}
				if (!empty($context['content'])) {
					$ref['content'] = (object) ['html' => $context['content']];
				}
				if (!empty($context['summary'])) {
					$ref['summary'] = $context['summary'];
				}
				if (!empty($context['attachment'])) {
					$this->handleAttachments($context['attachment'], $ref);
				}

				$item['refs'] = new \stdClass();
				$item['refs']->{$url} = (object) $ref;
			}
		}

		// Author information.
		$name = $photo = '';
		try {
			$target_actor = elgg()->activityPubUtility->getServer()->actor($activity->getActor());
			$name = $target_actor->get('name');
			$icon = $target_actor->get('icon');
			if (!empty($icon)) {
				$photo = elgg()->activityPubMediaCache->saveImageFromUrl($icon->get('url'), 'avatar');
			}

			$item['author'] = ['url' => $activity->getActor(), 'name' => $name, 'photo' => elgg_get_inline_url($photo)];

		} catch (\Exception $ignored) {}

		return (object) $item;
	}

	/**
	 * Handle attachments.
	 *
	 * @param $attachments
	 * @param $o
	 */
	protected function handleAttachments($attachments, &$o) {
		foreach ($attachments as $attachment) {
			if (!isset($attachment['type']) || !isset($attachment['url'])) {
				continue;
			}
			
			if (!in_array($attachment['type'], ['Audio', 'Document', 'Image', 'Video'], true)) {
				continue;
			}
			
			$type = (string) $attachment['type'];
			$url = (string) $attachment['url'];

			$mediaType = (string) $attachment['mediaType'];
			
			if (!empty($mediaType)) {
				if (strpos($mediaType, 'image/') === 0) {
					$type = 'Image';
				} else if (strpos($mediaType, 'video/') === 0) {
					$type = 'Video';
				} else if (strpos($mediaType, 'audio/') === 0) {
					$type = 'Audio';
				}
			}

			switch ($type) {
				case 'Image':
					if (!isset($o['photo'])) {
						$o['photo'] = [];
					}
					$o['photo'][] = elgg()->activityPubMediaCache->saveImageFromUrl($url, 'thumbs');
				break;
					
				case 'Video':
					if (!isset($o['video'])) {
						$o['video'] = [];
					}
					$o['video'][] = $url;
				break;
					
				case 'Audio':
					if (!isset($o['audio'])) {
						$o['audio'] = [];
					}
					$o['audio'][] = $url;
				break;
			};
		}
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
