<?php
/**
 * ActivityPub
 * @author Nikolai Shcherbin
 * @license GNU Affero General Public License version 3
 * @copyright (c) Nikolai Shcherbin 2024
 * @link https://wzm.me
**/

namespace Elgg\ActivityPub\Events\Groups;

use Elgg\ActivityPub\Entity\ActivityPubActivity;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class OnGroupObjectCreate {
	
	public function __invoke(\Elgg\Event $event) {
		$activity = $event->getParam('activity');
		$group = $event->getParam('group');
		
		if (!$group instanceof \ElggGroup || !$activity instanceof ActivityPubActivity) {
			return;
		}
		
		if (!elgg_is_active_plugin('groups') && !(bool) elgg_get_plugin_setting('enable_group', 'activitypub') && (!(bool) $group->activitypub_enable || !(bool) $group->activitypub_actor)) {
			return;
		}
		
		$owner = $activity->getOwnerEntity();
		if (!$owner instanceof \ElggUser && !$owner instanceof \ElggGroup) {
			return;
		}

		$actor = (string) elgg()->activityPubUtility->getActivityPubID($owner);
			
		$announce = new ActivityPubActivity();
		$announce->owner_guid = (int) $activity->owner_guid;
		$announce->access_id = (int) $activity->access_id;
		$announce->setMetadata('collection', ActivityPubActivity::OUTBOX);
		$announce->setMetadata('activity_type', 'Announce');
		$announce->setMetadata('actor', $actor);
		$announce->setMetadata('activity_object', (string) $activity->getURL());
		$announce->setMetadata('processed', 0);
		$announce->setMetadata('status', 0);
			
		if ($announce->canBeQueued()) {
			$announce->setMetadata('queued', 1);
		}
					
		if (!$announce->save()) {
			if ((bool) elgg_get_plugin_setting('log_general_inbox_error', 'activitypub')) {
				$this->log(elgg_echo('activitypub:activitypub_activity:save:error', ['Event: OnGroupObjectCreate, Group GUID: ' . (int) $group->guid . ', Activity GUID: ' . (int) $activity->guid]));
			}
			return false;
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
