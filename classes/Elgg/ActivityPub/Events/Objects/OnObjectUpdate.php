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
use Elgg\ActivityPub\Entity\FederatedObject;

class OnObjectUpdate {
	
	public function __invoke(\Elgg\Event $event) {
		$activity = $event->getObject();
		
		if (!$activity instanceof ActivityPubActivity) {
			return;
		}

		$user = $activity->getOwnerEntity();
		
		if (!$user instanceof \ElggUser) {
			return;
		}

		$entities = elgg_call(ELGG_IGNORE_ACCESS, function () use ($activity, $user) {
			return elgg_get_entities([
				'type' => 'object',
				'subtype' => [FederatedObject::SUBTYPE, 'comment'],
				'owner_guid' => (int) $user->guid,
				'metadata_name_value_pairs' => [
					[
						'name' => 'external_id',
						'value' => (string) $activity->getActivityObject(),
					],
				],
				'limit' => 1,
			]);
		});
		
		if (!empty($entities)) {
			$content = (string) $activity->getContent();

			if (!empty($content)) {
				foreach ($entities as $entity) {
					if (is_callable('mb_convert_encoding')) {
						$description = mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8');
					} else {
						$description = $content;
					}

					if (!empty((string) $entity->excerpt)) {
						$entity->excerpt = elgg_sanitize_input($description);
					}
	
					if (!empty((string) $entity->description)) {
						$entity->description = elgg_sanitize_input($description);
					}
					
					$entity->save();
				}
			}
		}
	}
}
