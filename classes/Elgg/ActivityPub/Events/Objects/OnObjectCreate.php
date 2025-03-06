<?php
/**
 * ActivityPub
 * @author Nikolai Shcherbin
 * @license GNU Affero General Public License version 3
 * @copyright (c) Nikolai Shcherbin 2024
 * @link https://wzm.me
**/

namespace Elgg\ActivityPub\Events\Objects;

use Elgg\ActivityPub\Entity\ActivityPubActivity;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class OnObjectCreate {
	
	public function __invoke(\Elgg\Event $event) {
		$entity = $event->getObject();
		
		if (!$entity instanceof \ElggObject) {
			return;
		}
		
	    $svc = elgg()->activityPubUtility;
		$subtypes = $svc->getDynamicSubTypes();

		if (!(bool) elgg_get_plugin_setting("can_activitypub:object:$entity->subtype", 'activitypub') && !in_array($entity->subtype, $subtypes)) {
			return;
		}
		
		if (!empty((int) $entity->activity_reference) || (int) $entity->activity_reference > 0) {
			return;
		}

		if ($entity->published_status === 'draft' || $entity->status === 'draft') {
			return;
		}
		
		$user = $entity->getOwnerEntity();
		
		if (!$user instanceof \ElggUser) {
			return;
		}
		
		if (!$user->isAdmin() && (!(bool) $user->getPluginSetting('activitypub', 'enable_activitypub') || !(bool) $user->activitypub_actor)) {
			return;
		}

		$group = $entity->getContainerEntity();

		if ($group instanceof \ElggGroup && (!elgg_is_active_plugin('groups') || !(bool) elgg_get_plugin_setting('enable_group', 'activitypub') || (!(bool) $group->activitypub_enable || !(bool) $group->activitypub_actor))) {
			return;
		}

		$entity_url = elgg_generate_url('view:activitypub:object', [
			'guid' => (int) $entity->guid,
		]);

		if ($entity instanceof \ElggComment) {
			$original_container = !elgg_is_active_plugin('theme') ? $entity->getThreadEntity()->getContainerEntity()->getContainerEntity() : $entity->getOriginalContainer()->getContainerEntity();

			if ($original_container instanceof \ElggGroup) {
				$group = $original_container;
			} else if ($entity->getContainerEntity()->getContainerEntity() instanceof \ElggGroup) {
				$group = $entity->getContainerEntity()->getContainerEntity();
			}
		}
		
		$activity = new ActivityPubActivity();
		$activity->owner_guid = ($group instanceof \ElggGroup) ? (int) $group->guid : (int) $user->guid;
		$activity->container_guid = ($group instanceof \ElggGroup) ? (int) $group->guid : (int) $user->guid;
		$activity->access_id = (int) $entity->access_id;
		//WIP - add more types, e.g. 'Add' to 'target' property context. See https://www.w3.org/TR/activitystreams-vocabulary/#dfn-add
		$activity->setMetadata('activity_type', 'Create');
		$activity->setMetadata('actor', $svc->getActivityPubID($user));
		$activity->setMetadata('activity_object', $entity_url);
		$activity->setMetadata('collection', ActivityPubActivity::OUTBOX);
		$activity->setMetadata('entity_subtype', $entity->subtype);
		$activity->setMetadata('entity_guid', (int) $entity->guid);
		$activity->setMetadata('processed', 0);
		$activity->setMetadata('status', 0);

		if ($activity->canBeQueued()) {
			$activity->setMetadata('queued', 1);
		}
		
		if (!$activity->save()) {
			if ((bool) elgg_get_plugin_setting('log_general_inbox_error', 'activitypub')) {
				$this->log(elgg_echo('activitypub:activitypub_activity:save:error', ['Event: OnObjectCreate, Object GUID: ' . (int) $entity->guid]));
			}
		}
		
		$entity->setMetadata('activity_reference', (int) $activity->guid);

		// send Announce if Group
		if ($group instanceof \ElggGroup) {
			elgg_trigger_event_results('group_announce', 'activitypub', [
				'activity' => $activity,
				'group' => $group,
			], true);
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
