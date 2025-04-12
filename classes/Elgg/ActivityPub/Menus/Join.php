<?php

/**
 * ActivityPub
 * @author Nikolai Shcherbin
 * @license GNU Affero General Public License version 3
 * @copyright (c) Nikolai Shcherbin 2022
 * @link https://wzm.me
**/

namespace Elgg\ActivityPub\Menus;

class Join
{
    /**
     * Register item to menu
     *
     * @param \Elgg\Event $event 'register', 'menu:activitypub_join'
     *
     * @return void|\Elgg\Menu\MenuItems
     */
    public function __invoke(\Elgg\Event $event): ?\Elgg\Menu\MenuItems
    {
        if (!elgg_is_logged_in()) {
            return null;
        }

        if (!(bool) elgg_get_plugin_setting('enable_group', 'activitypub')) {
            return null;
        }

        $entity = $event->getEntityParam();
        if (!$entity instanceof \ElggGroup || !(bool) elgg()->activityPubUtility->isEnabledGroup($entity)) {
            return null;
        }

        $user = elgg_get_logged_in_user_entity();

        if (!(bool) elgg()->activityPubUtility->isEnabledUser($user)) {
            return null;
        }

        $return = $event->getValue();

        $group_join = groups_get_group_join_menu_item($entity, $user);
        if (!empty($group_join)) {
            $group_join->setLinkClass('elgg-button elgg-button-action');
            $return[] = $group_join;
        }

        $group_leave = groups_get_group_leave_menu_item($entity, $user);
        if (!empty($group_leave)) {
            $group_leave->setLinkClass('elgg-button elgg-button-cancel');
            $return[] = $group_leave;
        }

        return $return;
    }
}
