<?php

namespace Elgg\ActivityPub\Views;

class SetupHead
{
    public function __invoke(\Elgg\Event $event)
    {

        $return = $event->getValue();

        if (!(bool) elgg_get_plugin_setting('enable_activitypub', 'activitypub')) {
            return $return;
        }

        //Publisher
        $owner = elgg_get_page_owner_entity();

        if (elgg_in_context('profile') || elgg_in_context('profile_view') || elgg_in_context('creator')) {
            if ($owner instanceof \ElggUser && (bool) $owner->getPluginSetting('activitypub', 'enable_activitypub') && (bool) $owner->activitypub_actor) {
                $return['metas'][] = [
                    'name' => 'fediverse:creator',
                    'content' => '@' . (string) $owner->activitypub_name,
                ];
            } elseif ($owner instanceof \ElggGroup && (bool) elgg_get_plugin_setting('enable_group', 'activitypub') && (bool) $owner->activitypub_enable && (bool) $owner->activitypub_actor) {
                $return['metas'][] = [
                    'name' => 'fediverse:creator',
                    'content' => '@' . (string) $owner->activitypub_groupname,
                ];
            }
        }

        return $return;
    }
}
