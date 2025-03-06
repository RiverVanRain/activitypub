<?php

namespace Elgg\ActivityPub\Controller;

class ActorController {
	/**
	 * Actor self routing callback.
	 *
	 * @return \Elgg\Http\Response
	 */
	public function __invoke(\Elgg\Request $request): \Elgg\Http\Response {
		$entity = $request->getEntityParam();

		if (!$entity instanceof \ElggEntity && $username = (string) $request->getParam('username')) {
			$entity = elgg_get_user_by_username($username);
		}

		if (!$entity instanceof \ElggEntity) {
			throw new \Elgg\Exceptions\Http\PageNotFoundException();
		}
		
		$actor = elgg()->activityPubActorFactory->fromEntity($entity);

		$data = array_merge($actor->getContextExport() , $actor->export());

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
