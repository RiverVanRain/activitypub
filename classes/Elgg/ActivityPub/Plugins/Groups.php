<?php

namespace Elgg\ActivityPub\Plugins;

use Elgg\Collections\Collection;
use Elgg\Groups\Tool;

/**
 * Support for the groups plugin
 */
class Groups {
	
	/**
	 * Register a group tool
	 *
	 * @param \Elgg\Event $event 'tool_options', 'group'
	 *
	 * @return null|Collection
	 */
	public static function registerTool(\Elgg\Event $event): ?Collection {
		if (!(bool) elgg_get_plugin_setting('enable_group', 'activitypub')) {
			return null;
		}
		
		$result = $event->getValue();
		
		$result[] = new Tool('activitypub', [
			'default_on' => true,
		]);
		
		return $result;
	}
}
