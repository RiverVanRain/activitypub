<?php

/**
 * ActivityPub
 * @author Nikolai Shcherbin
 * @license GNU Affero General Public License version 3
 * @copyright (c) Nikolai Shcherbin 2024
 * @link https://wzm.me
**/

namespace Elgg\ActivityPub\Actions;

class Search
{
    /**
     * Search routing callback.
     * Executes a search for users or content on the fediverse.
     */
    public function __invoke(\Elgg\Request $request)
    {
        if (!elgg_is_logged_in()) {
            throw new \Elgg\Exceptions\Http\EntityPermissionsException();
        }

        if (!(bool) elgg_get_plugin_setting('resolve_remote', 'activitypub')) {
            throw new \Elgg\Exceptions\Http\PageNotFoundException();
        }

        $user = elgg_get_logged_in_user_entity();
        if (!(bool) elgg_get_plugin_setting('enable_activitypub', 'activitypub') || !(bool) $user->getPluginSetting('activitypub', 'enable_activitypub') || !(bool) $user->activitypub_actor) {
            throw new \Elgg\Exceptions\Http\EntityPermissionsException();
        }

        $query = (string) $request->getParam('query');

        if (!$query) {
            return elgg_error_response(elgg_echo('activitypub:search:error:query'));
        }

        $results = [];

        $data = \Elgg\ActivityPub\Services\ResolveService::resolveQuery($query);

        foreach ($data['accounts'] as $account) {
            $result = [
                'type' => (string) $account['type'],
                'id' => (string) $account['id'],
                'title' => (string) $account['name'],
                'username' => (string) $account['preferredUsername'],
                'link' => (string) $account['url'],
                'icon' => [],
                'snippet' => null,
                'time' => null,
                'icon' => null,
            ];

            // summary
            if (isset($account['summary'])) {
                $result['snippet'] = (string) $account['summary'];
            }

            // Author icon
            if (isset($account['icon']) && is_array($account['icon'])) {
                $result['icon'] = (string) $account['icon']['url'];
            }

            $results[] = $result;
        }

        foreach ($data['statuses'] as $activity) {
            $result = [
                'type' => (string) $activity['type'],
                'id' => (string) $activity['id'],
                'title' => (string) elgg()->activityPubUtility->getActorName((string) $activity['attributedTo']),
                'username' => null,
                'link' => (string) $activity['url'],
                'snippet' => (string) $activity['content'],
                'time' => (string) $activity['published'],
                'icon' => (string) elgg()->activityPubUtility->getActorIcon((string) $activity['attributedTo']),
                'attachment' => [],
            ];

            if (isset($activity['attachment']) && is_array($activity['attachment'])) {
                $result['attachments'] = $activity['attachment'];
            }

            $results[] = $result;
        }

        return elgg_ok_response($results);
    }
}
