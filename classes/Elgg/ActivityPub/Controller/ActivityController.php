<?php

namespace Elgg\ActivityPub\Controller;

use Elgg\ActivityPub\Entity\ActivityPubActivity;

class ActivityController {
	/**
	 *  Activity routing callback.
	 *
	 * @return \Elgg\Http\Response
	 */
	public function __invoke(\Elgg\Request $request): \Elgg\Http\Response {
		$entity = $request->getEntityParam();

		if (!$entity instanceof ActivityPubActivity) {
			throw new \Elgg\Exceptions\Http\PageNotFoundException();
		}

		$build = elgg_call(ELGG_IGNORE_ACCESS, function() use ($entity) {
			return $entity->buildActivity();
		});

		$data = [
			'@context' => [
				ActivityPubActivity::CONTEXT_URL,
			],
		];

		$data = array_merge($data, $build);

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
