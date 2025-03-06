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

class OutboxController {
	/**
     * Outbox routing callback.
     */
	public function __invoke(\Elgg\Request $request): \Elgg\Http\Response {
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
		
		try {
			$limit = (int) $request->getParam('limit');
			
			if (!isset($limit) || $limit === 0) {
				$data = $this->getCollectionInfo($entity);
			} else {
				$data = $this->getCollectionItems($entity, (int) $request->getParam('offset'));
			}
			
			$response = new \Elgg\Http\OkResponse();
			
			$response->setHeaders([
				'Accept' => 'application/activity+json, application/ld+json; profile="https://www.w3.org/ns/activitystreams"',
				'Content-Type' => 'application/activity+json; charset=utf-8',
			]);
			
			$response->setContent(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
		} catch (\Exception $e) {
			elgg_log('Outbox routing callback error: ' . $e->getMessage(), \Psr\Log\LogLevel::ERROR);
			$response = new \Elgg\Http\ErrorResponse('', 400);
		}
		
		return $response;
	}

	/**
     * Get collection info.
     *
     * @return array
     */
	protected function getCollectionInfo(\ElggUser|\ElggGroup $entity): array {
		$options = [
			'types' => 'object',
			'subtypes' => ActivityPubActivity::SUBTYPE,
			'access_id' => ACCESS_PUBLIC,
			'owner_guid' => (int) $entity->guid,
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
					'name' => 'collection',
					'value' => ActivityPubActivity::OUTBOX,
				],
				[
					'name' => 'activity_type',
					'value' => elgg()->activityPubUtility->getOutboxIgnoreTypes(),
					'operand' => '!=',
				],
			],
		];
		
		$items_count = elgg_call(ELGG_IGNORE_ACCESS, function () use ($options) {
			return elgg_count_entities($options);
		});
		
		$url = $entity instanceof \ElggUser ? elgg_generate_url('view:activitypub:user:outbox', [
			'guid' => (int) $entity->guid,
		]) : elgg_generate_url('view:activitypub:group:outbox', [
			'guid' => (int) $entity->guid,
		]);

		$data = [
			'@context' => ActivityPubActivity::CONTEXT_URL,
			'id' => $url,
			'actor' => (string) elgg()->activityPubUtility->getActivityPubID($entity),
			'type' => 'OrderedCollection',
		];
		
		$limit = (int) max(get_input('limit', max(25, _elgg_services()->config->default_limit)), 0);
		
		if ($limit > 0 && $items_count > 0) {
			if ($items_count <= $limit) {
				$limit = $items_count;
			}
			
			$data['first'] = elgg_http_add_url_query_elements($url, [
				'limit' => $limit,
				'offset' => 0,
			]);
			
			$data['last'] = elgg_http_add_url_query_elements($url, [
				'limit' => $items_count,
			]);
		} else if ($limit === 0 && $items_count > 0) {
			$first_limit = _elgg_services()->config->default_limit;
			
			if ($items_count <= $first_limit) {
				$first_limit = $items_count;
			}
			
			$data['first'] = elgg_http_add_url_query_elements($url, [
				'limit' => $first_limit,
				'offset' => 0,
			]);
			
			$data['last'] = elgg_http_add_url_query_elements($url, [
				'limit' => $items_count,
			]);
		}
		
		$data['totalItems'] = $items_count;

		return $data;
	}

	/**
	 * Get collection items.
	 *
	 * @return array
	*/
	protected function getCollectionItems(\ElggUser|\ElggGroup $entity, int $offset = 0): array {
		$items = [];
		
		$limit = (int) max(get_input('limit', max(25, _elgg_services()->config->default_limit)), 0);
		
		$options = [
			'type' => 'object',
			'subtype' => ActivityPubActivity::SUBTYPE,
			'access_id' => ACCESS_PUBLIC,
			'owner_guid' => (int) $entity->guid,
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
					'name' => 'collection',
					'value' => ActivityPubActivity::OUTBOX,
				],
				[
					'name' => 'activity_type',
					'value' => elgg()->activityPubUtility->getOutboxIgnoreTypes(),
					'operand' => '!=',
				],
			],
			'limit' => $limit,
			'offset' => $offset,
		];

		$activities = elgg_call(ELGG_IGNORE_ACCESS, function () use ($options) {
			return elgg_get_entities($options);
		});
		
		foreach ($activities as $activity) {
			$items[] = $activity->buildActivity();
		}
		
		$url = $entity instanceof \ElggUser ? elgg_generate_url('view:activitypub:user:outbox', [
			'guid' => (int) $entity->guid,
		]) : elgg_generate_url('view:activitypub:group:outbox', [
			'guid' => (int) $entity->guid,
		]);

		$data = [
			'@context' => ActivityPubActivity::CONTEXT_URL,
			'id' => $url,
			'actor' => (string) elgg()->activityPubUtility->getActivityPubID($entity),
			'type' => 'OrderedCollectionPage',
			'partOf' => $url,
		];
		
		unset($options['limit']);
		unset($options['offset']);
		$count = elgg_call(ELGG_IGNORE_ACCESS, function () use ($options) {
			return elgg_count_entities($options);
		});
		
		if ($count > 0) {		
			$next_offset = $offset + 1;
			if ($next_offset >= $count) {
				$next_offset = abs($count - $limit);
			}
			
			$data['next'] = elgg_http_add_url_query_elements($url, [
				'limit' => $limit,
				'offset' => $next_offset,
			]);
			
			if ($next_offset > 0 && $offset > 0) {
				$data['prev'] = elgg_http_add_url_query_elements($url, [
					'limit' => $limit,
					'offset' => abs($offset - 1),
				]);
			}
			
			if ($count <= $limit) {
				unset($data['next']);
			}
		}

		$data['orderedItems'] = $items;

		return $data;
	}
}
