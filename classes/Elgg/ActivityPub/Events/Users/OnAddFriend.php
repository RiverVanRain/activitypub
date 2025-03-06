<?php
/**
 * ActivityPub
 * @author Nikolai Shcherbin
 * @license GNU Affero General Public License version 3
 * @copyright (c) Nikolai Shcherbin 2024
 * @link https://wzm.me
**/

namespace Elgg\ActivityPub\Events\Users;

use Elgg\ActivityPub\Entity\ActivityPubActivity;

class OnAddFriend {

	/**
	 * Listen to the create friend relationship to send friendship request
	 *
	 * @param \Elgg\Event $event 'create', 'relationship'
	 *
	 * @return void
	 */
	public function __invoke(\Elgg\Event $event) {
		
		$relationship = $event->getObject();
		if (!$relationship instanceof \ElggRelationship || $relationship->relationship !== 'friend') {
			return;
		}
		
		$remote_friend = get_user((int) $relationship->guid_two);
		if (!$remote_friend instanceof \Elgg\ActivityPub\Entity\FederatedUser) {
			return;
		}
		
		$actor = get_user((int) $relationship->guid_one);
		if (!$actor instanceof \ElggUser) {
			return;
		}
		
		$activity = new ActivityPubActivity();
		$activity->owner_guid = (int) $actor->guid;
		$activity->access_id = ACCESS_PUBLIC;
		$activity->setMetadata('collection', ActivityPubActivity::OUTBOX);
		$activity->setMetadata('activity_type', 'Follow');
		$activity->setMetadata('actor', elgg()->activityPubUtility->getActivityPubID($actor));
		$activity->setMetadata('activity_object', (string) $remote_friend->canonical_url);
		$activity->setMetadata('processed', 0);
		$activity->setMetadata('status', 0);
			
		if ($activity->canBeQueued()) {
			$activity->setMetadata('queued', 1);
		}
		
		if (!$activity->save()) {
			elgg_log(elgg_echo('activitypub:outbox:follow:error', [(int) $actor->guid, (int) $remote_friend->guid]), \Psr\Log\LogLevel::ERROR);
			return false;
		}
	}
}
