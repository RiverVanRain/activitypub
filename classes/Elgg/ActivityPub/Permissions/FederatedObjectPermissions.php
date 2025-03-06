<?php

namespace Elgg\ActivityPub\Permissions;

use Elgg\ActivityPub\Entity\FederatedObject;

/**
 * Permission event handler
 */
class FederatedObjectPermissions {
	
	/**
	 * Add permissions
	 *
	 * @param \Elgg\Event $event 'permissions_check', 'object'
	 *
	 * @return null|bool
	 */
	public static function editFederatedObject(\Elgg\Event $event): ?bool {
		$entity = $event->getEntityParam();
		if (!$entity instanceof FederatedObject) {
			return null;
		}

		if (elgg_is_admin_logged_in()) {
			return true;
		}

		return false;
	}
	
	/**
	 * Check delete permissions
	 *
	 * @param \Elgg\Event $event 'permissions_check:delete', 'object'
	 *
	 * @return null|bool
	 */
	public static function deleteFederatedObject(\Elgg\Event $event): ?bool {
		$entity = $event->getEntityParam();
		if (!$entity instanceof FederatedObject) {
			return null;
		}

		if (elgg_is_admin_logged_in()) {
			return true;
		}

		return false;
	}
}
