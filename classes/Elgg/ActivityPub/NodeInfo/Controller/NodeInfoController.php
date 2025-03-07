<?php

/**
 * NodeInfo
 * @author Nikolai Shcherbin
 * @license GNU Affero General Public License version 3
 * @copyright (c) Nikolai Shcherbin 2024
 * @link https://wzm.me
**/

namespace Elgg\ActivityPub\NodeInfo\Controller;

use Elgg\Database\QueryBuilder;

class NodeInfoController
{
    /**
     * Handle request.
     *
     * @param \Elgg\Request $request
     *   Information about the current HTTP request.
     *
     * @return \Elgg\Http\Response
     *   The JSON response.
     */
    public static function handleNodeinfoRequest(\Elgg\Request $request): \Elgg\Http\Response
    {
        $response = new \Elgg\Http\OkResponse();

        $response->setHeaders([
            'Content-Type' => 'application/json; charset=utf-8',
            'Access-Control-Allow-Origin' => '*',
        ]);

        $data = [
            'links' => [
                [
                    'rel' => 'http://nodeinfo.diaspora.software/ns/schema/2.1',
                    'href' => elgg_generate_url('view:activitypub:nodeinfo'),
                ],
                [
                    'rel' => 'https://www.w3.org/ns/activitystreams#Application',
                    'href' => elgg_generate_url('view:activitypub:application'),
                ],
            ],
        ];

        $response->setContent(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        return $response;
    }

    public static function nodeinfoContent(\Elgg\Request $request): \Elgg\Http\Response
    {
        $response = new \Elgg\Http\OkResponse();

        $response->setHeaders([
            'Content-Type' => 'application/json; charset=utf-8',
            'Access-Control-Allow-Origin' => '*',
        ]);

        $data = [
            'version' => '2.1',
            'software' => [
                'name' => 'elgg',
                'version' => (string) elgg_get_release(),
                'repository' => 'https://github.com/Elgg/Elgg/',
                'homepage' => (string) elgg_get_site_url(),
            ],
            'usage' => [
                'users' => [
                    'total' => (int) elgg_count_entities([
                        'type' => 'user',
                    ]),
                    'activeMonth' => (int) elgg_count_entities([
                        'type' => 'user',
                        'metadata_name_value_pairs' => ['banned' => 'no'],
                        'wheres' => [
                            function (QueryBuilder $qb, $main_alias) {
                                return $qb->between("{$main_alias}.last_action", time() - 2629743, time(), ELGG_VALUE_TIMESTAMP);
                            },
                        ]
                    ]),
                    'activeHalfyear' => (int) elgg_count_entities([
                        'type' => 'user',
                        'metadata_name_value_pairs' => ['banned' => 'no'],
                        'wheres' => [
                            function (QueryBuilder $qb, $main_alias) {
                                return $qb->between("{$main_alias}.last_action", time() - 15778458, time(), ELGG_VALUE_TIMESTAMP);
                            },
                        ]
                    ]),
                ],
                'localPosts' => (int) elgg_count_entities([
                    'types' => 'object',
                    'subtypes' => self::activeSubtypes(),
                ]),
                'localComments' => (int) elgg_count_entities([
                    'type' => 'object',
                    'subtype' => 'comment',
                ]),
            ],
            'openRegistrations' => (!_elgg_services()->config->allow_registration || _elgg_services()->config->elgg_maintenance_mode) ? false : true,
            'protocols' => ['activitypub'],
            'services' => [
                'inbound' => [],
                'outbound' => [],
            ],
            'metadata' => [
                'nodeName' => (string) elgg_get_site_entity()->getDisplayName(),
                'nodeDescription' => (string) elgg_get_site_entity()->description,
                'nodeIcon' => (string) elgg_get_site_entity()->getIconURL('medium'),
            ],
        ];

        $response->setContent(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        return $response;
    }

    /**
     * Handle request.
     *
     * @param \Elgg\Request $request
     *   Information about the current HTTP request.
     *
     * @return \Elgg\Http\Response
     *   The JSON response.
     */
    public static function handleNodeinfo2Request(\Elgg\Request $request): \Elgg\Http\Response
    {
        $response = new \Elgg\Http\OkResponse();

        $response->setHeaders([
            'Content-Type' => 'application/json; charset=utf-8',
            'Access-Control-Allow-Origin' => '*',
        ]);

        $data = [
            'version' => '2.0',
            'server' => [
                'baseUrl' => (string) elgg_get_site_url(),
                'name' => (string) elgg_get_site_entity()->getDisplayName(),
                'software' => 'elgg',
                'version' => (string) elgg_get_release(),
            ],
            'usage' => [
                'users' => [
                    'total' => (int) elgg_count_entities([
                        'type' => 'user',
                    ]),
                    'activeMonth' => (int) elgg_count_entities([
                        'type' => 'user',
                        'metadata_name_value_pairs' => ['banned' => 'no'],
                        'wheres' => [
                            function (QueryBuilder $qb, $main_alias) {
                                return $qb->between("{$main_alias}.last_action", time() - 2629743, time(), ELGG_VALUE_TIMESTAMP);
                            },
                        ]
                    ]),
                    'activeHalfyear' => (int) elgg_count_entities([
                        'type' => 'user',
                        'metadata_name_value_pairs' => ['banned' => 'no'],
                        'wheres' => [
                            function (QueryBuilder $qb, $main_alias) {
                                return $qb->between("{$main_alias}.last_action", time() - 15778458, time(), ELGG_VALUE_TIMESTAMP);
                            },
                        ]
                    ]),
                ],
                'localPosts' => (int) elgg_count_entities([
                    'types' => 'object',
                    'subtypes' => self::activeSubtypes(),
                ]),
                'localComments' => (int) elgg_count_entities([
                    'type' => 'object',
                    'subtype' => 'comment',
                ]),
            ],
            'openRegistrations' => (!_elgg_services()->config->allow_registration || _elgg_services()->config->elgg_maintenance_mode) ? false : true,
            'protocols' => ['activitypub'],
            'services' => [
                'inbound' => [],
                'outbound' => [],
            ],
        ];

        $response->setContent(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        return $response;
    }

    protected static function activeSubtypes(): array
    {
        $svc = elgg()->activityPubUtility;
        $subtypes = $svc->getDynamicSubTypes();

        $core_subtypes = [];

        foreach (
            [
            'blog',
            'comment',
            'messages',
            'river',
            'thewire',
            'file',
            'event',
            'poll',
            'album',
            'photo',
            'topic',
            'topic_post',
            ] as $subtype
        ) {
            if (!(bool) elgg_get_plugin_setting("can_activitypub:object:{$subtype}", 'activitypub')) {
                continue;
            }

            $core_subtypes[] = $subtype;
        }

        return array_merge($subtypes, $core_subtypes);
    }
}
