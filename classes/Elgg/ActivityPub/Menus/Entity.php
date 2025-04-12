<?php

/**
 * ActivityPub
 * @author Nikolai Shcherbin
 * @license GNU Affero General Public License version 3
 * @copyright (c) Nikolai Shcherbin 2022
 * @link https://wzm.me
**/

namespace Elgg\ActivityPub\Menus;

class Entity
{
    /**
     * Register item to menu
     *
     * @param \Elgg\Event $event 'register', 'menu:entity'
     *
     * @return void|\Elgg\Menu\MenuItems
     */
    public static function activityEntity(\Elgg\Event $event): ?\Elgg\Menu\MenuItems
    {
        if (!elgg_is_admin_logged_in()) {
            return null;
        }

        $entity = $event->getEntityParam();
        if (!$entity instanceof \Elgg\ActivityPub\Entity\ActivityPubActivity) {
            return null;
        }

        $return = $event->getValue();

        $return[] = \ElggMenuItem::factory([
            'name' => 'edit',
            'href' => elgg_http_add_url_query_elements('ajax/view/activitypub/edit', [
                'guid' => (int) $entity->guid,
            ]),
            'text' => elgg_echo('edit'),
            'icon' => 'edit',
            'class' => 'elgg-lightbox',
            'data-colorbox-opts' => json_encode([
                'width' => '1000px',
                'height' => '98%',
                'maxWidth' => '98%',
                'overlayClose' => false,
                'escKey' => false,
            ]),
            'deps' => ['elgg/lightbox'],
        ]);

        return $return;
    }

    /**
     * Register item to menu
     *
     * @param \Elgg\Event $event 'register', 'menu:entity'
     *
     * @return void|\Elgg\Menu\MenuItems
     */
    public static function userEntity(\Elgg\Event $event): ?\Elgg\Menu\MenuItems
    {
        if (!(bool) elgg_get_plugin_setting('enable_activitypub', 'activitypub')) {
            return null;
        }

        if (elgg_is_logged_in()) {
            return null;
        }

        $user = $event->getEntityParam();
        if (!$user instanceof \ElggUser || !(bool) elgg()->activityPubUtility->isEnabledUser($user)) {
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
        ]);

        return $return;
    }

    /**
     * Register item to menu
     *
     * @param \Elgg\Event $event 'register', 'menu:entity'
     *
     * @return void|\Elgg\Menu\MenuItems
     */
    public static function groupEntity(\Elgg\Event $event): ?\Elgg\Menu\MenuItems
    {
        if (!(bool) elgg_get_plugin_setting('enable_group', 'activitypub')) {
            return null;
        }

        if (elgg_is_logged_in()) {
            return null;
        }

        $group = $event->getEntityParam();
        if (!$group instanceof \ElggGroup || !(bool) elgg()->activityPubUtility->isEnabledGroup($group)) {
            return null;
        }

        $return = $event->getValue();

        $return[] = \ElggMenuItem::factory([
            'name' => 'activitypub_join',
            'href' => elgg_generate_url('activitypub:group:join', [
                'guid' => (int) $group->guid
            ]),
            'text' => elgg_echo('activitypub:group:join'),
            'icon' => '<i class="openwebicons-activitypub" style="font-size: 16px;"></i>',
        ]);

        return $return;
    }

    /**
     * Register item to menu
     *
     * @param \Elgg\Event $event 'register', 'menu:entity'
     *
     * @return void|\Elgg\Menu\MenuItems
     */
    public static function groupActivityPubTool(\Elgg\Event $event): ?\Elgg\Menu\MenuItems
    {
        if (!(bool) elgg_get_plugin_setting('enable_group', 'activitypub')) {
            return null;
        }

        if (!elgg_is_logged_in()) {
            return null;
        }

        $group = $event->getEntityParam();
        if (!$group instanceof \ElggGroup || !(bool) $group->canEdit()) {
            return null;
        }

        $return = $event->getValue();

        $return[] = \ElggMenuItem::factory([
            'name' => 'activitypub_tool',
            'href' => elgg_generate_url('activitypub:group:settings', [
                'guid' => (int) $group->guid,
            ]),
            'text' => elgg_echo('activitypub:group:settings'),
            'icon' => '<i class="openwebicons-activitypub" style="font-size: 16px;"></i>',
        ]);

        return $return;
    }
}
