<?php

/**
 * ActivityPub
 * @author Nikolai Shcherbin
 * @license GNU Affero General Public License version 3
 * @copyright (c) Nikolai Shcherbin 2022
 * @link https://wzm.me
**/

$profile_plugin = true;

if (elgg_is_active_plugin('theme')) {
    $profile_plugin = false;
}

$remote_user_searchable = false;

if ((bool) elgg_get_plugin_setting('remote_user_searchable', 'activitypub')) {
    $remote_user_searchable = true;
}

$remote_group_searchable = false;

if ((bool) elgg_get_plugin_setting('remote_group_searchable', 'activitypub')) {
    $remote_group_searchable = true;
}

$remote_object_searchable = false;

if ((bool) elgg_get_plugin_setting('remote_object_searchable', 'activitypub')) {
    $remote_object_searchable = true;
}

return [
    'plugin' => [
        'name' => 'ActivityPub',
        'version' => '0.4',
        'dependencies' => [
            'friends' => [
                'position' => 'after',
                'must_be_active' => true, // WIP - make it not required
            ],
            'members' => [
                'position' => 'after',
                'must_be_active' => false,
            ],
            'profile' => [
                'position' => 'after',
                'must_be_active' => $profile_plugin,
            ],
            'theme' => [
                'position' => 'before',
                'must_be_active' => false,
            ],
            'verification' => [
                'position' => 'after',
                'must_be_active' => false,
            ],
        ],
    ],

    'bootstrap' => \Elgg\ActivityPub\Bootstrap::class,

    //ENTITIES
    'entities' => [
        //Activity
        [
            'type' => 'object',
            'subtype' => 'activitypub_activity',
            'class' => \Elgg\ActivityPub\Entity\ActivityPubActivity::class,
            'capabilities' => [
                'commentable' => false,
                'likable' => false,
                'searchable' => false,
            ],
        ],
        //Federated
        [
            'type' => 'user',
            'subtype' => 'federated',
            'class' => \Elgg\ActivityPub\Entity\FederatedUser::class,
            'capabilities' => [
                'commentable' => false,
                'likable' => false,
                'searchable' => $remote_user_searchable,
            ],
        ],
        [
            'type' => 'group',
            'subtype' => 'federated',
            'class' => \Elgg\ActivityPub\Entity\FederatedGroup::class,
            'capabilities' => [
                'commentable' => false,
                'likable' => false,
                'searchable' => $remote_group_searchable,
            ],
        ],
        [
            'type' => 'object',
            'subtype' => 'federated',
            'class' => \Elgg\ActivityPub\Entity\FederatedObject::class,
            'capabilities' => [
                'commentable' => true,
                'likable' => true,
                'searchable' => $remote_object_searchable,
            ],
        ],
    ],

    //ACTIONS
    'actions' => [
        'activitypub/settings/save' => [
            'controller' => \Elgg\ActivityPub\Actions\Admin\SettingsAction::class,
            'access' => 'admin',
        ],
        'admin/activitypub/types' => [
            'controller' => \Elgg\ActivityPub\Actions\Admin\SettingsAction::class,
            'access' => 'admin',
        ],
        'activitypub/edit' => [
            'controller' => \Elgg\ActivityPub\Actions\Admin\Edit::class,
            'access' => 'admin',
        ],
        'activitypub/users/follow' => [
            'controller' => \Elgg\ActivityPub\Actions\Users\Follow::class,
            'access' => 'public',
        ],
        'activitypub/users/follow_remote' => [
            'controller' => \Elgg\ActivityPub\Actions\Users\FollowRemote::class,
        ],
        'activitypub/search' => [
            'controller' => \Elgg\ActivityPub\Actions\Search::class,
        ],
        'group_tools/activitypub' => [
            'controller' => \Elgg\ActivityPub\Actions\Groups\GroupTool::class,
        ],
        'activitypub/groups/join' => [
            'controller' => \Elgg\ActivityPub\Actions\Groups\Join::class,
            'access' => 'public',
        ],
        'activitypub/groups/join_remote' => [
            'controller' => \Elgg\ActivityPub\Actions\Groups\JoinRemote::class,
        ],
    ],

    //EVENTS
    'events' => [
        'action:validate' => [
            'group_tools/activitypub' => [
                \Elgg\ActivityPub\Events\Groups\OnGroupSettingsSave::class => [],
            ],
            'plugins/usersettings/save' => [
                \Elgg\ActivityPub\Events\Users\OnUserSettingsSave::class => [],
            ],
        ],
        'container_logic_check' => [
            'object' => [
                \Elgg\ActivityPub\Permissions\GroupToolContainerLogicCheck::class => [],
            ],
        ],
        'create:after' => [
            'object' => [
                \Elgg\ActivityPub\Events\Objects\OnMessageSend::class => [],
                \Elgg\ActivityPub\Events\Objects\OnObjectCreate::class => [],
            ],
        ],
        'create' => [
            'annotation' => [
                \Elgg\ActivityPub\Events\Annotations\OnAnnotationCreate::class => [],
            ],
            'relationship' => [
                \Elgg\ActivityPub\Events\Users\OnAddFriend::class => [],
            ],
        ],
        'cron' => [
            'minute' => [
                'Elgg\ActivityPub\Cron::processOutbox' => [],
                'Elgg\ActivityPub\Cron::processInbox' => [],
            ],
            'daily' => [
                'Elgg\ActivityPub\Cron::removeInbox' => [],
                'Elgg\ActivityPub\Cron::removeOutbox' => [],
            ],
        ],
        'delete' => [
            'relationship' => [
                \Elgg\ActivityPub\Events\Users\OnDeleteFriend::class => [],
            ],
            'object' => [
                \Elgg\ActivityPub\Events\Objects\OnObjectDelete::class => [],
            ],
            'user' => [
                \Elgg\ActivityPub\Events\Users\OnUserDelete::class => [],
            ],
        ],
        'head' => [
            'page' => [
                \Elgg\ActivityPub\Views\SetupHead::class => [],
            ],
        ],
        'group_announce' => [
            'activitypub' => [
                \Elgg\ActivityPub\Events\Groups\OnGroupObjectCreate::class => [],
            ],
        ],
        'import' => [
            'activitypub' => [
                \Elgg\ActivityPub\Events\Objects\OnRiverCreate::class => [],
                \Elgg\ActivityPub\Events\Objects\OnTheWireCreate::class => [],
            ],
        ],
        'permissions_check' => [
            'object' => [
                '\Elgg\ActivityPub\Permissions\FederatedObjectPermissions::editFederatedObject' => [],
            ],
        ],
        'permissions_check:delete' => [
            'object' => [
                '\Elgg\ActivityPub\Permissions\FederatedObjectPermissions::deleteFederatedObject' => [],
            ],
        ],
        'publish' => [
            'object' => [
                \Elgg\ActivityPub\Events\Objects\OnObjectPublish::class => [],
            ],
        ],
        'register' => [
            'menu:activitypub_follow' => [
                \Elgg\ActivityPub\Menus\Follow::class => [],
            ],
            'menu:activitypub_join' => [
                \Elgg\ActivityPub\Menus\Join::class => [],
            ],
            'menu:admin_header' => [
                \Elgg\ActivityPub\Menus\SettingsMenu::class => [],
            ],
            'menu:entity' => [
                '\Elgg\ActivityPub\Menus\Entity::activityEntity' => [],
                '\Elgg\ActivityPub\Menus\Entity::groupActivityPubTool' => [],
                '\Elgg\ActivityPub\Menus\Entity::groupEntity' => [],
                '\Elgg\ActivityPub\Menus\Entity::userEntity' => [],
            ],
            'menu:filter:members' => [
                'Elgg\ActivityPub\Menus\Members::register' => ['priority' => 800],
            ],
            'menu:title' => [
                '\Elgg\ActivityPub\Menus\Title::userTitle' => [],
                '\Elgg\ActivityPub\Menus\Title::groupTitle' => [],
            ],
            'menu:topbar' => [
                \Elgg\ActivityPub\Menus\Topbar::class => [],
            ],
        ],
        'sanitize' => [
            'input' => [
                \Elgg\Input\ValidateInputHandler::class => ['unregister' => true],
                \Elgg\ActivityPub\Hooks\HtmlawedConfig::class => ['priority' => 1],
            ],
        ],
        'tool_options' => [
            'group' => [
                '\Elgg\ActivityPub\Plugins\Groups::registerTool' => [],
            ],
        ],
        'update:after' => [
            'group' => [
                \Elgg\ActivityPub\Events\Groups\OnGroupEdit::class => [],
            ],
            'object' => [
                \Elgg\ActivityPub\Events\Objects\OnObjectEdit::class => [],
                \Elgg\ActivityPub\Events\Objects\OnObjectUpdate::class => [],
            ],
        ],
    ],

    //ROUTES
    'routes' => [
        //api
        'default:view:webfinger' => [
            'path' => '/.well-known/webfinger/{resource?}',
            'controller' => [\Elgg\ActivityPub\WebFinger\Controller\WebfingerController::class, 'handleRequest'],
            'walled' => false,
        ],
        'default:view:nodeinfo' => [
            'path' => '/.well-known/nodeinfo',
            'controller' => [\Elgg\ActivityPub\NodeInfo\Controller\NodeInfoController::class, 'handleNodeinfoRequest'],
            'walled' => false,
        ],
        'view:activitypub:nodeinfo' => [
            'path' => '/activitypub/nodeinfo',
            'controller' => [\Elgg\ActivityPub\NodeInfo\Controller\NodeInfoController::class, 'nodeinfoContent'],
            'walled' => false,
        ],
        'default:view:nodeinfo2' => [
            'path' => '/.well-known/x-nodeinfo2',
            'controller' => [\Elgg\ActivityPub\NodeInfo\Controller\NodeInfoController::class, 'handleNodeinfo2Request'],
            'walled' => false,
        ],

        //general
        'view:activitypub:inbox' => [
            'path' => '/activitypub/inbox',
            'controller' => \Elgg\ActivityPub\Controller\SharedInboxController::class,
            'walled' => false,
        ],
        'view:activitypub:outbox' => [
            'path' => '/activitypub/outbox',
            'controller' => \Elgg\ActivityPub\Controller\SharedOutboxController::class,
            'walled' => false,
        ],
        'view:activitypub:interactions' => [
            'path' => '/activitypub/interactions/{uri?}',
            'controller' => \Elgg\ActivityPub\Controller\InteractionsController::class,
            'middleware' => [
                \Elgg\Router\Middleware\Gatekeeper::class,
            ],
        ],
        'view:activitypub:search' => [
            'path' => '/activitypub/search',
            'resource' => 'activitypub/search',
            'middleware' => [
                \Elgg\Router\Middleware\Gatekeeper::class,
            ],
        ],

        //application
        'view:activitypub:application' => [
            'path' => '/activitypub/application',
            'controller' => \Elgg\ActivityPub\Controller\ApplicationController::class,
            'walled' => false,
        ],

        //users
        'view:activitypub:user' => [
            'path' => '/activitypub/users/{guid}',
            'controller' => \Elgg\ActivityPub\Controller\ActorController::class,
            'walled' => false,
        ],
        'view:activitypub:user:inbox' => [
            'path' => '/activitypub/users/{guid}/inbox',
            'controller' => \Elgg\ActivityPub\Controller\InboxController::class,
            'walled' => false,
        ],
        'view:activitypub:user:outbox' => [
            'path' => '/activitypub/users/{guid}/outbox',
            'controller' => \Elgg\ActivityPub\Controller\OutboxController::class,
            'walled' => false,
        ],
        'view:activitypub:user:followers' => [
            'path' => '/activitypub/users/{guid}/followers',
            'controller' => \Elgg\ActivityPub\Controller\FollowersController::class,
            'walled' => false,
        ],
        /*
        'activitypub:user:followers:synchronization' => [
            'path' => '/activitypub/users/{guid}/followers/synchronization',
            'controller' => \Elgg\ActivityPub\Controller\FollowerSynchronization::class,
            'walled' => false,
        ],
        */
        'view:activitypub:user:following' => [
            'path' => '/activitypub/users/{guid}/following',
            'controller' => \Elgg\ActivityPub\Controller\FollowingController::class,
            'walled' => false,
        ],
        'view:activitypub:user:liked' => [
            'path' => '/activitypub/users/{guid}/liked',
            'controller' => \Elgg\ActivityPub\Controller\LikedController::class,
            'walled' => false,
        ],
        'activitypub:user:follow' => [
            'path' => '/activitypub/user/{guid}/follow',
            'resource' => 'activitypub/user/follow',
            'walled' => false,
            'middleware' => [
                \Elgg\Router\Middleware\LoggedOutGatekeeper::class,
            ],
        ],

        //groups
        'view:activitypub:group' => [
            'path' => '/activitypub/groups/{guid}',
            'controller' => \Elgg\ActivityPub\Controller\ActorController::class,
            'walled' => false,
            'required_plugins' => [
                'groups',
            ],
        ],
        'view:activitypub:group:inbox' => [
            'path' => '/activitypub/groups/{guid}/inbox',
            'controller' => \Elgg\ActivityPub\Controller\InboxController::class,
            'walled' => false,
            'required_plugins' => [
                'groups',
            ],
        ],
        'view:activitypub:group:outbox' => [
            'path' => '/activitypub/groups/{guid}/outbox',
            'controller' => \Elgg\ActivityPub\Controller\OutboxController::class,
            'walled' => false,
            'required_plugins' => [
                'groups',
            ],
        ],
        'view:activitypub:group:followers' => [
            'path' => '/activitypub/groups/{guid}/followers',
            'controller' => \Elgg\ActivityPub\Controller\FollowersController::class,
            'walled' => false,
            'required_plugins' => [
                'groups',
            ],
        ],
        'activitypub:group:followers:synchronization' => [
            'path' => '/activitypub/groups/{guid}/followers/synchronization',
            'controller' => \Elgg\ActivityPub\Controller\FollowerSynchronization::class,
            'walled' => false,
            'required_plugins' => [
                'groups',
            ],
        ],
        'view:activitypub:group:following' => [
            'path' => '/activitypub/groups/{guid}/following',
            'controller' => \Elgg\ActivityPub\Controller\FollowingController::class,
            'walled' => false,
            'required_plugins' => [
                'groups',
            ],
        ],
        'view:activitypub:group:liked' => [
            'path' => '/activitypub/groups/{guid}/liked',
            'controller' => \Elgg\ActivityPub\Controller\LikedController::class,
            'walled' => false,
            'required_plugins' => [
                'groups',
            ],
        ],
        'activitypub:group:settings' => [
            'path' => '/activitypub/group/{guid}/settings',
            'resource' => 'activitypub/group/settings',
            'middleware' => [
                \Elgg\Router\Middleware\GroupPageOwnerCanEditGatekeeper::class,
            ],
            'required_plugins' => [
                'groups',
            ],
        ],
        'activitypub:group:join' => [
            'path' => '/activitypub/group/{guid}/join',
            'resource' => 'activitypub/group/join',
            'walled' => false,
            'middleware' => [
                \Elgg\Router\Middleware\LoggedOutGatekeeper::class,
            ],
            'required_plugins' => [
                'groups',
            ],
        ],

        //objects
        'view:object:activitypub_activity' => [
            'path' => '/activitypub/activity/{guid}',
            'controller' => \Elgg\ActivityPub\Controller\ActivityController::class,
            'walled' => false,
        ],
        'view:activitypub:object' => [
            'path' => '/activitypub/object/{guid}',
            'controller' => \Elgg\ActivityPub\Controller\ObjectController::class,
            'walled' => false,
        ],

        //federated
        'view:object:federated' => [
            'path' => '/federated/view/{guid}/{title?}',
            'resource' => 'activitypub/federated/object/view',
            'walled' => false,
        ],

        //members
        'collection:user:user:local' => [
            'path' => '/members/local',
            'resource' => 'activitypub/members/local',
        ],
        'collection:user:user:remote' => [
            'path' => '/members/remote',
            'resource' => 'activitypub/members/remote',
        ],
    ],

    'views' => [
        'default' => [
            'openwebicons/' => __DIR__ . '/vendor/pfefferle/openwebicons/',
        ],
    ],

    'view_extensions' => [
        'admin.css' => [
            'theme/admin/activitypub.css' => ['priority' => 800],
        ],
         'elgg.css' => [
            'theme/activitypub.css' => ['priority' => 800],
        ],
        'page/elements/head' => [
            'activitypub/header' => [],
        ],
    ],

    'view_options' => [
        'activitypub/edit' => ['ajax' => true],
        'activitypub/search/results' => ['ajax' => true],
    ],

    //SETTINGS
    'settings' => [
        'enable_activitypub' => false,
        'enable_group' => false,
        'resolve_remote' => false,
        'process_outbox_handler' => false,
        'remove_outbox_activities' => false,
        'process_inbox_handler' => false,
        'remove_inbox_activities' => false,
        'import_inbox' => 'disable',
        'log_general_inbox_error' => false,
        'log_error_signature' => false,
        'log_activity_error' => false,
        'server_logger' => false,
        'instance_host' => 'localhost',
        'instance_port' => 443,
        'instance_types' => 'strict',
        'http_timeout' => 10,
        'http_retries' => 2,
        'http_sleep' => 5,
        'cache_enable' => true,
        'cache_ttl' => 3600,
    ],

    //USER SETTINGS
    'user_settings' => [
        'enable_discoverable' => true,
    ],
];
