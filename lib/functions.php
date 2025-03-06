<?php
/**
 * ActivityPub
 * @author Nikolai Shcherbin
 * @license GNU Affero General Public License version 3
 * @copyright (c) Nikolai Shcherbin 2022
 * @link https://wzm.me
**/

/**
 * Returns the path without the hostname from a URL.
 *
 * @param $url
 *
 * @return string $path  The path
 */
function activitypub_get_path(string $url): string {
	$path = str_replace(elgg_get_site_url(), '', $url);
	
	if (empty($path)) {
		$path = '/';
	}
	
	return $path;
}

/**
 * Returns GUID from a URL.
 *
 * @param $url
 *
 * @return int $guid
 */
function activitypub_get_guid(string $url): int {
	$target = activitypub_get_path($url);
	
	$guid = [];
	
	if (strpos($target, 'activitypub/object/') !== false) {
		$id = str_replace('activitypub/object/', '', $target);
		$id = explode('/', $id);
		$guid[] = $id[0];
	} else {
		$types = (array) elgg_extract('object', elgg_entity_types_with_capability('searchable'), []);
		
		$slugs = elgg_get_plugin_setting('objects_slugs', 'activitypub') ?: [];
		
		if (is_string($slugs)) {
			$slugs = elgg_string_to_array($slugs);
		}
		
		$objects = array_merge($types, $slugs);
		
		foreach ($objects as $subtype) {
			if (strpos($target, "{$subtype}/view/") === false) {
				continue;
			}

			$id = str_replace("{$subtype}/view/", '', $target);
			$id = explode('/', $id);
			$guid[] = $id[0];
		}
	}

	return (!empty($guid)) ? (int) $guid[0] : 0;
}

