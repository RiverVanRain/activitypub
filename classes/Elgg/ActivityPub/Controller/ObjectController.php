<?php

namespace Elgg\ActivityPub\Controller;

use Elgg\ActivityPub\Entity\ActivityPubActivity;
use Elgg\ActivityPub\Entity\FederatedObject;
use Elgg\Database\Clauses\OrderByClause;

class ObjectController {
	/**
	 * Object self routing callback.
	 * 
	 * @vars title, guid, _route ("view:object:SUBTYPE")
	 *
	 * @return \Elgg\Http\Response
	 */
	public function __invoke(\Elgg\Request $request): \Elgg\Http\Response {
		$entity = $request->getEntityParam();
		
		if (!$entity instanceof \ElggObject && !$entity instanceof FederatedObject) {
			throw new \Elgg\Exceptions\Http\PageNotFoundException();
		}

		// remote object
		if ($entity instanceof FederatedObject) {
			$data = elgg()->activityPubObjectFactory->fromUri((string) $entity->canonical_url);

			$response = new \Elgg\Http\OkResponse();
	
			$response->setHeaders([
				'Accept' => 'application/activity+json, application/ld+json; profile="https://www.w3.org/ns/activitystreams"',
				'Content-Type' => 'application/activity+json; charset=utf-8',
				'Access-Control-Allow-Origin' => '*',
			]);

			$response->setContent(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
			
			return $response;
		}

		// local object
		$activityPubUtility = elgg()->activityPubUtility;
		$subtypes = $activityPubUtility->getDynamicSubTypes();

		if (!(bool) elgg_get_plugin_setting("can_activitypub:object:$entity->subtype", 'activitypub') && !in_array($entity->subtype, $subtypes)) {
			throw new \Elgg\Exceptions\Http\PageNotFoundException();
		}

		$object = elgg()->activityPubObjectFactory->fromEntity($entity);

		$data = array_merge($object->getContextExport(), $object->export());

		$response = new \Elgg\Http\OkResponse();
	
		$response->setHeaders([
			'Accept' => 'application/activity+json, application/ld+json; profile="https://www.w3.org/ns/activitystreams"',
			'Content-Type' => 'application/activity+json; charset=utf-8',
			'Access-Control-Allow-Origin' => '*',
		]);

		$response->setContent(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
		
		return $response;
	}
}
