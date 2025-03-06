<?php

namespace Elgg\ActivityPub\Actions\Admin;

class SettingsAction {

	public function __invoke(\Elgg\Request $request) {

		$params = (array) $request->getParam('params');
		$flush_cache = (bool) $request->getParam('flush_cache', false);
		$plugin_id = (string) $request->getParam('plugin_id');
		$plugin = elgg_get_plugin_from_id($plugin_id);
		
		if (!$plugin) {
			return elgg_error_response(elgg_echo('plugins:settings:save:fail', [$plugin_id]));
		}
		
		$dynamic_types = $this->prepareTypes($request->getParam('dynamic_types'));
		if (isset($dynamic_types)) {
			$params['dynamic_types'] = $dynamic_types;
		}

		$plugin_name = $plugin->getDisplayName();

		$result = false;

		foreach ($params as $k => $v) {
			if (is_array($v)) {
				$v = serialize($v);
			}
			
			$result = $plugin->setSetting($k, $v);
			if (!$result) {
				return elgg_error_response(elgg_echo('plugins:settings:save:fail', [$plugin_name]));
			}
		}

		if ($flush_cache) {
			elgg_invalidate_caches();
			elgg_clear_caches();
		}

		return elgg_ok_response('', elgg_echo('plugins:settings:save:ok', [$plugin_name]));
	}
	
	public function prepareTypes($dynamic_types) {
		$config = [];
		if (is_array($dynamic_types)) {
			foreach ($dynamic_types as $name => $options) {
				if (isset($options['policy'])) {
					for ($i = 0; $i < count($options['policy']['subtype']); $i++) {
						if (!empty($options['policy']['aptype'][$i])) {
							$config[$name]['policy'][$i] = [
								'subtype' => $options['policy']['subtype'][$i],
								'aptype' => $options['policy']['aptype'][$i],
								'can_activitypub' => $options['policy']['can_activitypub'][$i],
							];
						}
					}
				}
			}
		}

		return $config;
	}
}