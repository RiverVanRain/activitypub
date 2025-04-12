<?php

use Elgg\ActivityPub\Entity\FederatedGroup;
use Elgg\ActivityPub\Entity\FederatedUser;

elgg_gatekeeper();

$user = elgg_get_logged_in_user_entity();

if (!(bool) elgg()->activityPubUtility->isEnabledUser($user)) {
    throw new \Elgg\Exceptions\Http\EntityPermissionsException();
}

$results = (array) elgg_extract('response', $vars, []);

if (empty($results)) {
    echo elgg_format_element('div', ['class' => 'mtm'], elgg_echo('activitypub:search:no_results'));
    return;
}

$actor = (string) elgg()->activityPubUtility->getActivityPubID($user);

foreach ($results as $result) {
    $link = (string) $result['link'];

    $follower = elgg()->activityPubManager->getEntityFromUri((string) $result['id']);
    if ($follower instanceof FederatedUser || $follower instanceof FederatedGroup) {
        $link = (string) $follower->getURL();
    }

    //avatar
    $icon = false;
    if (!empty((string) $result['icon'])) {
        $avatar = elgg_view('output/url', [
            'href' => $link,
            'text' => elgg_view('output/img', [
                'src' => (string) $result['icon'],
                'alt' => (string) $result['title'],
            ]),
        ]);

        $icon = elgg_format_element('div', ['class' => 'elgg-avatar elgg-avatar-small'], $avatar);
    }

    //title
    $name = elgg_format_element('h3', [], elgg_view('output/url', [
        'href' => $link,
        'text' => (string) $result['title'],
    ]));

    $title = elgg_format_element('div', ['class' => 'elgg-listing-summary-title'], $name);

    //subtitle
    $username = false;
    if (!empty((string) $result['username'])) {
        $username = elgg_format_element('div', [], '@' . (string) $result['username']);
    }

    $time = false;
    if (!empty($result['time'])) {
        $timestamp = strtotime($result['time']);

        $time = elgg_format_element('div', [], elgg_view('output/friendlytime', [
            'time' => (int) $timestamp,
        ]));
    }

    $summary = elgg_format_element('div', ['class' => 'elgg-listing-summary-subtitle elgg-subtext'], $title . $username . $time);

    if (null !== (string) $result['snippet']) {
        $summary .= elgg_format_element('div', ['class' => 'mtm elgg-output'], elgg_view('output/longtext', [
            'value' => (string) $result['snippet'],
        ]));
    }

    //attachments
    if (isset($result['attachments']) && !empty($result['attachments'])) {
        $summary .= elgg_format_element('div', ['class' => 'activitypub-attachments'], elgg_view('activitypub/object/attachments', [
            'attachments' => (array) $result['attachments'],
        ]));
    }

    $follow = false;
    $object = (string) $result['id'];

    $isRemoteFollow = (bool) elgg()->activityPubUtility->isRemoteFollow($object, $actor);

    // Person
    if ((string) $result['type'] === 'Person') {
        if ($follower instanceof \ElggUser) {
            $link = (string) $follower->getURL();
            $follow = elgg_view_menu('activitypub_follow', [
                'entity' => $follower
            ]);
        } else {
            if (!$isRemoteFollow) {
                $follow = elgg_format_element('div', ['class' => 'elgg-menu-activitypub-follow-container'], elgg_view('output/url', [
                    'href' => elgg_generate_action_url('activitypub/users/follow_remote', [
                        'remote_friend' => $object,
                    ]),
                    'text' => elgg_echo('activitypub:user:follow'),
                    'icon' => 'user-plus',
                    'class' => 'elgg-button elgg-button-action',
                ]));
            } else {
                $follow = elgg_format_element('div', ['class' => 'elgg-menu-activitypub-follow-container'], elgg_view('output/url', [
                    'href' => false,
                    'text' => elgg_echo('activitypub:user:pending'),
                    'icon' => 'hourglass-o',
                    'class' => 'elgg-button elgg-state-disabled',
                ]));
            }
        }
    }

    // Group
    if ((string) $result['type'] === 'Group') {
        if ($follower instanceof \ElggGroup) {
            $link = (string) $follower->getURL();
            $follow = elgg_view_menu('activitypub_join', [
                'entity' => $follower
            ]);
        } else {
            if (!$isRemoteFollow) {
                $follow = elgg_format_element('div', ['class' => 'elgg-menu-activitypub-follow-container'], elgg_view('output/url', [
                    'href' => elgg_generate_action_url('activitypub/groups/join_remote', [
                        'remote_group' => $object,
                    ]),
                    'text' => elgg_echo('activitypub:group:join'),
                    'icon' => 'sign-in',
                    'class' => 'elgg-button elgg-button-action'
                ]));
            } else {
                $follow = elgg_format_element('div', ['class' => 'elgg-menu-activitypub-follow-container'], elgg_view('output/url', [
                    'href' => false,
                    'text' => elgg_echo('activitypub:group:pending'),
                    'icon' => 'hourglass-o',
                    'class' => 'elgg-button elgg-state-disabled',
                ]));
            }
        }
    }

    $summary .= elgg_format_element('div', ['class' => 'elgg-listing-summary-subtitle elgg-subtext'], elgg_view('output/url', [
        'href' => (string) $result['link'],
        'text' => elgg_echo('activitypub:search:remote_url'),
        'icon' => (in_array((string) $result['type'], ['Person', 'Group'])) ? 'user' : 'file-text-o',
    ]));

    $content = elgg_format_element('div', ['class' => 'activitypub-search-results-body'], $follow . $summary);

    echo elgg_format_element('li', ['class' => 'elgg-item activitypub-search-results-item'], elgg_view_image_block($icon, $content));
}
