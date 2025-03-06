<?php
/**
 * ActivityPub
 * @author Nikolai Shcherbin
 * @license GNU Affero General Public License version 3
 * @copyright (c) Nikolai Shcherbin 2024
 * @link https://wzm.me
**/

namespace Elgg\ActivityPub\Events\Users;

class OnUserSettingsSave {

	public function __invoke(\Elgg\Event $event) {
		
		$request = $event->getParam('request');
		$user = get_user((int) $request->getParam('user_guid'));
		
		if (!$user || !$request instanceof \Elgg\Request) {
			return null;
		}
		
		if (!(bool) elgg_extract('enable_activitypub', $request->getParam('params'))) {
			$user->setMetadata('activitypub_actor', 0);
			
			return true;
		}
		
		$name = (string) $user->username . '@' . (string) elgg_get_site_entity()->getDomain();
		
		$svc = elgg()->activityPubSignature;
		
		if ($svc->generateKeys((string) $user->username)) {
			$user->setMetadata('activitypub_actor', 1);
			$user->setMetadata('activitypub_name', $name);
			
			return true;
		} else {
			return elgg_error_response(elgg_echo('activitypub:keys:generate:entity:error'));
		}
	}
}
