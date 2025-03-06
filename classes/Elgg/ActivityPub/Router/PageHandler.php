<?php

namespace Elgg\ActivityPub\Router;

use Elgg\Exceptions\HttpException;
use Elgg\Http\ErrorResponse;

/**
 * PageHandler
 */
class PageHandler {
		
	/**
	 * Set resource for ActivityPub
	 *
	 * @param \Elgg\Event $event 'route:config', 'all'
	 *
	 * @return array
	 */
	public static function alterRoute(\Elgg\Event $event) {
		$accept = $_SERVER['HTTP_ACCEPT'] ?? '';
		$type = $event->getType();
		
		if (str_starts_with($accept, 'application/activity+json')) {
			$config = $event->getValue();
			
			if (str_starts_with($type, 'view:user') || str_starts_with($type, 'view:group')) {
				$config['controller'] = \Elgg\ActivityPub\Controller\ActorController::class;
			} else if ($type === 'view:object:activitypub_activity') {
				$config['controller'] = \Elgg\ActivityPub\Controller\ActivityController::class;
			} else if (str_starts_with($type, 'view:object:') || str_starts_with($type, 'view:activitypub:object')) {
				$config['controller'] = \Elgg\ActivityPub\Controller\ObjectController::class;
			}
			
			return $config;
		}
	}
}
