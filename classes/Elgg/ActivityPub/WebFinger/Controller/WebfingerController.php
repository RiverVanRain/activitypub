<?php
/**
 * WebFinger
 * @author Nikolai Shcherbin
 * @license GNU Affero General Public License version 3
 * @copyright (c) Nikolai Shcherbin 2024
 * @link https://wzm.me
**/

namespace Elgg\ActivityPub\WebFinger\Controller;

use Elgg\ActivityPub\WebFinger\JsonRd\JsonRd;
use Elgg\ActivityPub\WebFinger\JsonRd\JsonRdLink;

class WebfingerController {
	
	/**
	 * Handle request.
	 *
	 * @param \Elgg\Request $request
	 *   Information about the current HTTP request.
	 *
	 * @return \Elgg\Http\Response
	 *   The JSON response.
	 */
	public static function handleRequest(\Elgg\Request $request): \Elgg\Http\Response {
		$resource = (string) $request->getParam('resource');
		
		if (empty($resource)) {
			throw new \Elgg\Exceptions\Http\PageNotFoundException();
		}
		
		$httpRequest = new \Elgg\Http\Request();
		
		$httpRequest->setParam('resource', (string) $request->getParam('resource'));
		
		$httpRequest->setParam('host', (string) $request->getHttpRequest()->server->get('HTTP_HOST'));
		
		$params = (new WebfingerParameters())
		  ->setRequest($httpRequest)
		  ->getParams();
		  
		$json_rd = new JsonRd();

		$event = self::getProfile($json_rd, $request, $params);
		
		if ($event && !empty($event->getLinks())) {
			$response = new \Elgg\Http\OkResponse();
		} else {
			// If no links were returned, set a not found status code.
			throw new \Elgg\Exceptions\Http\PageNotFoundException();
		}
		
		$response->setHeaders([
			'Content-Type' => 'application/jrd+json; charset=utf-8',
			'Access-Control-Allow-Origin' => '*',
		]);

		$response->setContent(json_encode($event->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
		
		return $response;
	}
	
	public static function getProfile($json_rd, $request, $params) {
		// Subject should always be set
		$subject = (string) $request->getParam('resource');
		if (!empty($subject)) {
			$json_rd->setSubject($subject);
		}
		
		// APP
		// Determine if there is an application name
		if ($subject === (string) elgg_get_site_entity()->getDomain() . '@' . (string) elgg_get_site_entity()->getDomain() || $subject === 'acct:' . (string) elgg_get_site_entity()->getDomain() . '@' . (string) elgg_get_site_entity()->getDomain()) {
			if (!str_starts_with($subject, 'acct:')) {
				$json_rd->addAlias('acct:' . $subject);
			} else if (str_starts_with($subject, "acct:")) {
				$subject = substr($subject, 5);
				$json_rd->addAlias($subject);
			}
			
			// email 
			$json_rd->addAlias('mailto:' . (string) elgg_get_site_entity()->getEmailAddress());
			
			// URL
			$json_rd->addAlias((string) elgg_generate_url('view:activitypub:application'));
			$json_rd->addAlias((string) elgg_get_site_url());
				
			$link = new JsonRdLink();
				
			$link->setRel('http://webfinger.net/rel/profile-page')
				->setType('text/html')
				->setHref((string) elgg_generate_url('view:activitypub:application'));
			$json_rd->addLink($link);
				
			// Photo
			$link = new JsonRdLink();
				
			$link->setRel('http://webfinger.net/rel/avatar')
				->setType((string) elgg_get_site_entity()->getIcon('large')->getMimeType())
				->setHref((string) elgg_get_site_entity()->getIconURL([
					'type' => 'icon',
					'size' => 'large',
					'use_cookie' => false,
				]));
			$json_rd->addLink($link);
				
			// Self
			$link = new JsonRdLink();
					
			$link->setRel('self')
				->setType('application/activity+json')
				->setHref((string) elgg_generate_url('view:activitypub:application'))
				->setProperties([
					'https://www.w3.org/ns/activitystreams#type' => 'Application'
				]);
			$json_rd->addLink($link);
				
			//OStatus
			$link = new JsonRdLink();
					
			$link->setRel('http://ostatus.org/schema/1.0/subscribe')
				->setTemplate((string) elgg_generate_url('view:activitypub:interactions') . '?uri={uri}');
			$json_rd->addLink($link);
				
			// Webmention
			if ((bool) elgg_get_plugin_setting('enable_webmention', 'indieweb')) {
				$webmention_server = !empty((string) elgg_get_plugin_setting('webmention_server', 'indieweb')) ? (string) elgg_get_plugin_setting('webmention_server', 'indieweb') : (string) elgg_generate_url('default:view:webmention');
					
				$link = new JsonRdLink();
					
				$link->setRel('webmention')
					->setHref($webmention_server);
				$json_rd->addLink($link);
					
				$link = new JsonRdLink();
					
				$link->setRel('http://webmention.org/')
					->setHref($webmention_server);
				$json_rd->addLink($link);
			}
				
			return $json_rd;
		}
		
		// USER/GROUP
		// Determine if there is an account path for a requested name
		if (isset($params[WebfingerParameters::ACCOUNT_KEY_NAME])) {
			if ($entity = elgg()->activityPubUtility->getGroupByName($params[WebfingerParameters::ACCOUNT_KEY_NAME])) {
				return self::setProfile($json_rd, $entity, $subject);
			} else if ($entity = elgg_get_user_by_username($params[WebfingerParameters::ACCOUNT_KEY_NAME])) {
				return self::setProfile($json_rd, $entity, $subject);
			} else {
				elgg_log(elgg_echo('activitypub:webfinger:resource:no_user'), \Psr\Log\LogLevel::ERROR);
			}
		}
		
		return [];
	}

	public static function setProfile($json_rd, \ElggUser|\ElggGroup $entity, string $subject) {
		if (!$entity) {
			return false;
		}
		
		if ($entity->access_id !== ACCESS_PUBLIC) {
			return false;
		}
		
		if (!str_starts_with($subject, 'acct:')) {
			$json_rd->addAlias('acct:' . $subject);
		} else if (str_starts_with($subject, 'acct:')) {
			$subject = substr($subject, 5);
			$json_rd->addAlias($subject);
		}
				
		//Contact email
		if ($entity instanceof \ElggUser && !elgg_is_empty($entity->getProfileData('contactemail'))) {
			$json_rd->addAlias('mailto:' . (string) $entity->getProfileData('contactemail'));
		}
				
		//Profile URL
		$url = (string) $entity->getURL();
		$json_rd->addAlias($url);
				
		$link = new JsonRdLink();
				
		$link->setRel('http://webfinger.net/rel/profile-page')
			->setType('text/html')
			->setHref($url);
		$json_rd->addLink($link);
				
		//Profile photo
		$link = new JsonRdLink();
				
		$link->setRel('http://webfinger.net/rel/avatar')
			->setType((string) $entity->getIcon('large')->getMimeType())
			->setHref((string) elgg()->activityPubUtility->getActivityPubActorImage($entity, 'icon', 'large'));
		$json_rd->addLink($link);
				
		// Self
		$link = new JsonRdLink();
					
		$link->setRel('self')
			->setType('application/activity+json')
			->setHref((string) elgg()->activityPubUtility->getActivityPubID($entity));
		$json_rd->addLink($link);
				
		//OStatus
		$link = new JsonRdLink();
					
		$link->setRel('http://ostatus.org/schema/1.0/subscribe')
			->setTemplate((string) elgg_generate_url('view:activitypub:interactions') . '?uri={uri}');
		$json_rd->addLink($link);
				
		// Webmention
		if ((bool) elgg_get_plugin_setting('enable_webmention', 'indieweb')) {
			$webmention_server = !empty((string) elgg_get_plugin_setting('webmention_server', 'indieweb')) ? (string) elgg_get_plugin_setting('webmention_server', 'indieweb') : (string) elgg_generate_url('default:view:webmention');
					
			$link = new JsonRdLink();
					
			$link->setRel('webmention')
				->setHref($webmention_server);
			$json_rd->addLink($link);
					
			$link = new JsonRdLink();
					
			$link->setRel('http://webmention.org/')
				->setHref($webmention_server);
			$json_rd->addLink($link);
		}
				
		return $json_rd;
	}
}
