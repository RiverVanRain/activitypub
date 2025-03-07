<?php

/**
 * ActivityPub
 * @author Nikolai Shcherbin
 * @license GNU Affero General Public License version 3
 * @copyright (c) Nikolai Shcherbin 2022
 * @link https://wzm.me
**/

namespace Elgg\ActivityPub\Menus;

class Title
{
    /**
     * Register item to menu
     *
     * @param \Elgg\Event $event 'register', 'menu:title'
     *
     * @return void|\Elgg\Menu\MenuItems
     */
    public static function userTitle(\Elgg\Event $event): ?\Elgg\Menu\MenuItems
    {
        if (elgg_is_active_plugin('theme')) {
            return null;
        }

        if (!(bool) elgg_get_plugin_setting('enable_activitypub', 'activitypub')) {
            return null;
        }

        if (elgg_is_logged_in()) {
            return null;
        }

        $user = $event->getEntityParam();
        if (!$user instanceof \ElggUser || !(bool) $user->getPluginSetting('activitypub', 'enable_activitypub') || !(bool) $user->activitypub_actor) {
            return null;
        }

        $return = $event->getValue();

        $return[] = \ElggMenuItem::factory([
            'name' => 'activitypub_follow',
            'href' => elgg_generate_url('activitypub:user:follow', [
                'guid' => (int) $user->guid
            ]),
            'text' => elgg_echo('activitypub:user:follow'),
            'icon' => '<i class="openwebicons-activitypub" style="font-size: 16px;"></i>',
            'section' => 'action',
            'class' => 'elgg-button elgg-button-action',
        ]);

        return $return;
    }

    /**
     * Register item to menu
     *
     * @param \Elgg\Event $event 'register', 'menu:title'
     *
     * @return void|\Elgg\Menu\MenuItems
     */
    public static function groupTitle(\Elgg\Event $event): ?\Elgg\Menu\MenuItems
    {
        if (elgg_is_active_plugin('theme')) {
            return null;
        }

        if (!(bool) elgg_get_plugin_setting('enable_group', 'activitypub')) {
            return null;
        }

        if (elgg_is_logged_in()) {
            return null;
        }

        $group = $event->getEntityParam();
        if (!$group instanceof \ElggGroup || !(bool) $group->activitypub_enable || !(bool) $group->activitypub_actor) {
            return null;
        }

        $return = $event->getValue();

        $return[] = \ElggMenuItem::factory([
            'name' => 'activitypub_follow',
            'href' => elgg_generate_url('activitypub:group:follow', [
                'guid' => (int) $group->guid
            ]),
            'text' => elgg_echo('activitypub:group:follow'),
            'icon' => '<i class="openwebicons-activitypub" style="font-size: 16px;"></i>',
            'class' => 'elgg-button elgg-button-action',
        ]);

        return $return;
    }
}
