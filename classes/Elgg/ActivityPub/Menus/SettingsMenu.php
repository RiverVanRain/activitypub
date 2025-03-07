<?php

/**
 * ActivityPub
 * @author Nikolai Shcherbin
 * @license GNU Affero General Public License version 3
 * @copyright (c) Nikolai Shcherbin 2022
 * @link https://wzm.me
**/

namespace Elgg\ActivityPub\Menus;

class SettingsMenu
{
    /**
     * Setup page menu
     *
     * @param Event $event Event
     */
    public function __invoke(\Elgg\Event $event): ?\Elgg\Menu\MenuItems
    {
        if (!elgg_is_admin_logged_in() || !elgg_in_context('admin')) {
            return null;
        }

        $menu = $event->getValue();
        /* @var $menu \Elgg\Menu\MenuItems */

        $menu[] = \ElggMenuItem::factory([
            'name' => 'activitypub',
            'href' => false,
            'text' => elgg_echo('settings:activitypub'),
            'icon' => '<i class="openwebicons-activitypub" style="font-size: 16px;"></i>',
        ]);

        //Basic config
        $menu[] = \ElggMenuItem::factory([
            'name' => 'activitypub:settings',
            'parent_name' => 'activitypub',
            'href' => elgg_normalize_url('admin/plugin_settings/activitypub'),
            'text' => elgg_echo('admin:activitypub:settings'),
            'priority' => 100,
        ]);

        //Types
        $menu[] = \ElggMenuItem::factory([
            'name' => 'activitypub:types',
            'parent_name' => 'activitypub',
            'href' => elgg_normalize_url('admin/activitypub/types'),
            'text' => elgg_echo('admin:activitypub:types'),
            'priority' => 200,
        ]);

        //Activities
        $menu[] = \ElggMenuItem::factory([
            'name' => 'activitypub:activities',
            'parent_name' => 'activitypub',
            'href' => elgg_normalize_url('admin/activitypub/activities'),
            'text' => elgg_echo('admin:activitypub:activities'),
            'priority' => 300,
        ]);

        return $menu;
    }
}
