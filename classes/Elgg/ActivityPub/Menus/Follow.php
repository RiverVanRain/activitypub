<?php

/**
 * ActivityPub
 * @author Nikolai Shcherbin
 * @license GNU Affero General Public License version 3
 * @copyright (c) Nikolai Shcherbin 2022
 * @link https://wzm.me
**/

namespace Elgg\ActivityPub\Menus;

class Follow
{
    /**
     * Register item to menu
     *
     * @param \Elgg\Event $event 'register', 'menu:activitypub_follow'
     *
     * @return void|\Elgg\Menu\MenuItems
     */
    public function __invoke(\Elgg\Event $event): ?\Elgg\Menu\MenuItems
    {
        if (!elgg_is_logged_in()) {
            return null;
        }

        $user = $event->getEntityParam();
        if (!$user instanceof \ElggUser || !(bool) elgg()->activityPubUtility->isEnabledUser($user)) {
            return null;
        }

        /* @var $return \Elgg\Menu\MenuItems */
        $return = $event->getValue();

        $menu_items = _elgg_friends_get_add_friend_menu_items($user, true);

        $return->merge($menu_items);

        return $return;
    }
}
