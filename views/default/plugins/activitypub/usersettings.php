<?php

if (!(bool) elgg_get_plugin_setting('enable_activitypub', 'activitypub')) {
    return false;
}

$user_guid = (int) elgg_extract('user_guid', $vars);

$user = get_user($user_guid);
if (!$user || $user instanceof \Elgg\ActivityPub\Entity\FederatedUser) {
    return;
}

elgg_import_esm('js/activitypub/usersettings');

echo elgg_view_field([
    '#type' => 'checkbox',
    '#label' => elgg_echo('settings:activitypub:user:enable_activitypub'),
    '#help' => elgg_echo('settings:activitypub:user:enable_activitypub:help'),
    'name' => 'params[enable_activitypub]',
    'default' => 0,
    'value' => 1,
    'checked' => (bool) $user->getPluginSetting('activitypub', 'enable_activitypub'),
    'switch' => true,
    'id' => 'usersettings-enable-activitypub',
]);

if ((bool) $user->getPluginSetting('activitypub', 'enable_activitypub') && (bool) $user->activitypub_actor) {
    $actor_name = (string) $user->username . '@' . (string) elgg_get_site_entity()->getDomain();

    echo elgg_format_element('h4', ['class' => 'mbm'], elgg_echo('activitypub:user:is_actor:header', [$actor_name, $actor_name]));
}

//check global domains settings
$global_whitelisted = false;
$global_whitelisted_domains = (string) elgg_get_plugin_setting('activitypub_global_whitelisted_domains', 'activitypub');

if (!empty($global_whitelisted_domains)) {
    $global_whitelisted =  elgg_format_element('div', [], elgg_echo('activitypub:global_whitelisted_domains') . ': ' . $global_whitelisted_domains);
}

$global_blocked = false;
$global_blocked_domains = (string) elgg_get_plugin_setting('activitypub_global_blocked_domains', 'activitypub');

if (!empty($global_blocked_domains)) {
    $global_blocked =  elgg_format_element('div', [], elgg_echo('activitypub:global_blocked_domains') . ': ' . $global_blocked_domains);
}

//check user whitelisted domains
$whitelisted_domains = (string) $user->getPluginSetting('activitypub', 'activitypub_whitelisted_domains');
$whitelisted_domains = preg_split('/\\r\\n?|\\n/', $whitelisted_domains);
$whitelisted_domains = array_filter($whitelisted_domains);

//check user blocked domains
$blocked_domains = (string) $user->getPluginSetting('activitypub', 'activitypub_blocked_domains');
$blocked_domains = preg_split('/\\r\\n?|\\n/', $blocked_domains);
$blocked_domains = array_filter($blocked_domains);

echo elgg_view_field([
    '#type' => 'fieldset',
    'class' => (bool) $user->getPluginSetting('activitypub', 'enable_activitypub') ? '' : 'hidden',
    'id' => 'usersettings-activitypub',
    'fields' => [
        // Discoverability flag
        [
            '#type' => 'checkbox',
            '#label' => elgg_echo('settings:activitypub:user:enable_discoverable'),
            '#help' => elgg_echo('settings:activitypub:user:enable_discoverable:help'),
            'name' => 'params[enable_discoverable]',
            'default' => 0,
            'value' => 1,
            'checked' => (bool) $user->getPluginSetting('activitypub', 'enable_discoverable'),
            'switch' => true,
        ],
        [
            '#type' => 'fieldset',
            'legend' => elgg_echo('settings:activitypub:user:domains'),
            'fields' => [
                [
                    '#html' => elgg_format_element('div', ['class' => 'elgg-field-help elgg-text-help'], elgg_echo('settings:activitypub:user:domains:help')),
                ],
                [
                    '#type' => 'plaintext',
                    '#label' => elgg_echo('settings:activitypub:user:whitelisted_domains'),
                    '#help' => elgg_echo('settings:activitypub:user:whitelisted_domains:help', [$global_whitelisted]),
                    'name' => 'params[activitypub_whitelisted_domains]',
                    'value' => !empty($whitelisted_domains) ? implode("\r\n", $whitelisted_domains) : '',
                ],
                [
                    '#type' => 'plaintext',
                    '#label' => elgg_echo('settings:activitypub:user:blocked_domains'),
                    '#help' => elgg_echo('settings:activitypub:user:blocked_domains:help', [$global_blocked]),
                    'name' => 'params[activitypub_blocked_domains]',
                    'value' => !empty($blocked_domains) ? implode("\r\n", $blocked_domains) : '',
                ],
            ],
        ],
    ],
]);
