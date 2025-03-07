<?php

/**
 * ActivityPub
 * @author Nikolai Shcherbin
 * @license GNU Affero General Public License version 3
 * @copyright (c) Nikolai Shcherbin 2022
 * @link https://wzm.me
**/

namespace Elgg\ActivityPub\Menus;

class Topbar
{
    /**
     * Register item to menu
     *
     * @param \Elgg\Event $event 'register', 'menu:topbar'
     *
     * @return void|\Elgg\Menu\MenuItems
     */
    public function __invoke(\Elgg\Event $event): ?\Elgg\Menu\MenuItems
    {
        if (!(bool) elgg_get_plugin_setting('enable_activitypub', 'activitypub')) {
            return null;
        }

        if (!elgg_is_logged_in() || elgg_get_logged_in_user_entity() instanceof \Elgg\ActivityPub\Entity\FederatedUser) {
            return null;
        }

        $username = elgg_get_logged_in_user_entity()->username;

        $return = $event->getValue();

        $return[] = \ElggMenuItem::factory([
            'name' => 'activitypub',
            'href' => elgg_normalize_url("settings/plugins/{$username}/activitypub"),
            'text' => elgg_echo('activitypub:user'),
            'icon' => '<i class="openwebicons-activitypub" style="font-size: 16px;"></i>',
            'section' => 'alt',
            'parent_name' => 'account',
        ]);

        // search on Fediverse
        if ((bool) elgg_get_plugin_setting('resolve_remote', 'activitypub')) {
            $return[] = \ElggMenuItem::factory([
                'name' => 'search',
                'text' => false,
                'title' => elgg_echo('activitypub:search'),
                'href' => elgg_generate_url('view:activitypub:search'),
                'icon' => '<i class="openwebicons-federated" style="font-size: 16px;"></i>',
            ]);
        }

        return $return;
    }
}
