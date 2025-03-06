<?php

namespace Elgg\ActivityPub\Controller;

class ApplicationController {
	/**
	 * App self routing callback.
	 *
	 * @return \Elgg\Http\Response
	 */
	public function __invoke(\Elgg\Request $request): \Elgg\Http\Response {
		$actor = elgg()->activityPubActorFactory->buildApplicationActor();

		$data = array_merge($actor->getContextExport(), $actor->export());

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
