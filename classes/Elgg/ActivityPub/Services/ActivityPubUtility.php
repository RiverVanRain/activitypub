<?php

namespace Elgg\ActivityPub\Services;

use ActivityPhp\Server;
use Elgg\ActivityPub\Entity\ActivityPubActivity;
use Elgg\ActivityPub\Entity\FederatedUser;
use Elgg\Traits\Di\ServiceFacade;
use GuzzleHttp\Client;

class ActivityPubUtility
{
    use ServiceFacade;

    /**
     * Returns registered service name
     * @return string
     */
    public static function name()
    {
        return 'activityPubUtility';
    }

    /**
     * {@inheritdoc}
     */
    public function __get($name)
    {
        return $this->$name;
    }

    /**
     * {@inheritdoc}
     */
    public function getActivityPubName(\ElggUser|\ElggGroup $actor): string
    {
        return $actor->activitypub_name;
    }

    /**
     * {@inheritdoc}
     */
    public function getActivityPubID(\ElggEntity $actor): string
    {
        if ($actor instanceof \ElggSite) {
            return elgg_generate_url('view:activitypub:application');
        }

        if ($actor instanceof \ElggUser) {
            return elgg_generate_url('view:activitypub:user', [
                'guid' => (int) $actor->guid,
            ]);
        }

        if ($actor instanceof \ElggGroup) {
            return elgg_generate_url('view:activitypub:group', [
                'guid' => (int) $actor->guid,
            ]);
        }

        if ($actor instanceof \ElggObject) {
            return elgg_generate_url('view:activitypub:object', [
                'guid' => (int) $actor->guid,
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getGroupByName(string $name): ?\ElggGroup
    {
        if (empty($name)) {
            return null;
        }

        $groups = elgg_get_entities([
            'types' => 'group',
            'metadata_name_value_pairs' => [
                [
                    'name' => 'activitypub_groupname',
                    'value' => $name,
                    'case_sensitive' => false,
                ],
            ],
            'limit' => 1,
        ]);

        return $groups ? $groups[0] : null;
    }

    /**
     * ActivityPub actor image
     *
     * @param \ElggUser|\ElggGroup $actor    The ActivityPub actor
     * @param $type               Object icon type: icon, cover (default: icon)
     * @param $size               The icon size to draw for the entity (default: medium)
     */
    public function getActivityPubActorImage(\ElggEntity $actor, string $type = 'icon', string $size = 'medium')
    {
        if ($actor->hasIcon('master', $type)) {
            return $actor->getIconURL([
                'type' => $type,
                'size' => $size,
                'use_cookie' => false,
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getServer(array $config = [])
    {
        $name = (string) elgg_get_site_entity()->getDisplayName();
        $version = (string) elgg_get_release();
        $appUrl = (string) elgg_get_site_url();

        $logger = (bool) elgg_get_plugin_setting('block_logger', 'activitypub') ?
        [
            'driver' => '\Psr\Log\NullLogger'
        ] :
        [
            'driver' => 'Monolog\Logger',
            'stream' => elgg_get_data_path() . 'activitypub/logs/server.log',
            'channel' => 'ActivityPub',
        ];

        $cache = (bool) elgg_get_plugin_setting('cache_enable', 'activitypub') ?
        [
            'ttl' => (int) elgg_get_plugin_setting('cache_ttl', 'activitypub') ?? 3600,
            'stream' => elgg_get_data_path() . 'activitypub/cache/',
        ] :
        [
            'enabled' => false,
        ];

        $config += [
            'http' => [
                'agent' => "({$name}/{$version}; +{$appUrl})",
                'retries' => (int) elgg_get_plugin_setting('http_retries', 'activitypub') ?? 2,
                'sleep'   => (int) elgg_get_plugin_setting('http_sleep', 'activitypub') ?? 5,
            ],
            'cache' => $cache,
            'logger'   => $logger,
            'instance'   => [
                'host' => (string) elgg_get_plugin_setting('instance_host', 'activitypub') ?? 'localhost',
                'port' => (int) elgg_get_plugin_setting('instance_port', 'activitypub') ?? 443,
                'types' => (string) elgg_get_plugin_setting('instance_types', 'activitypub') ?? 'strict',
            ],
        ];

        return new Server($config);
    }

    /**
     * {@inheritdoc}
     */
    public function alterNodeInfo(array &$data)
    {
        $data['protocols'] = ['activitypub'];
        $data['usage']['users']['total'] = elgg_call(ELGG_IGNORE_ACCESS, function () {
            return elgg_count_entities([
                'types' => 'user',
                'metadata_name_value_pairs' => [
                    [
                        'name' => 'activitypub_actor',
                        'value' => 1,
                    ],
                ],
            ]);
        });

        $data['usage']['localPosts'] = elgg_call(ELGG_IGNORE_ACCESS, function () {
            return elgg_count_entities([
                'types' => 'object',
                'subtypes' => ActivityPubActivity::SUBTYPE,
                'access_id' => ACCESS_PUBLIC,
                'metadata_name_value_pairs' => [
                    [
                        'name' => 'status',
                        'value' => 1,
                    ],
                    [
                        'name' => 'processed',
                        'value' => 1,
                    ],
                    [
                        'name' => 'collection',
                        'value' => ['outbox', 'liked'],
                    ],
                ],
            ]);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getOutboxIgnoreTypes()
    {
        return ['Follow', 'Join', 'Accept', 'Undo', 'Leave', 'Delete'];
    }

    /**
     * {@inheritdoc}
     */
    public function getTimelineTypes()
    {
        return ['Create', 'Like', 'Dislike', 'Announce'];
    }

    /**
     * {@inheritdoc}
     */
    public function getNotificationTypes()
    {
        return ['Like', 'Dislike', 'Announce', 'Follow', 'Join', 'Create'];
    }

    /**
     * {@inheritdoc}
     */
    public function getSiteSettings()
    {
        $entity = elgg_get_plugin_from_id('activitypub');
        $settings = unserialize($entity->getSetting('dynamic_types'));

        if (!$settings) {
            return [];
        }

        return $settings;
    }

    /**
     * {@inheritdoc}
     */
    public function getDynamicSubTypes()
    {
        $settings = $this->getSiteSettings();

        if (empty($settings)) {
            return [];
        }

        $types = $settings['dynamic']['policy'];

        $return = [];

        if (is_array($types)) {
            foreach ($types as $p) {
                if (!(bool) $p['can_activitypub']) {
                    continue;
                }

                $return[] = $p['subtype'];
            }
        }

        return $return;
    }

    /**
     * {@inheritdoc}
     */
    public function getDynamicApTypes($subtype)
    {
        if (!$subtype) {
            return null;
        }

        $settings = $this->getSiteSettings();

        if (empty($settings)) {
            return null;
        }

        $types = $settings['dynamic']['policy'];

        if (is_array($types)) {
            foreach ($types as $p) {
                if (!(bool) $p['can_activitypub']) {
                    continue;
                }

                if ($p['subtype'] === $subtype) {
                    return $p['aptype'];
                }
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getActivityPubObject(\ElggObject $entity)
    {
        $subtype = $entity->subtype;

        // Core types
        $subtypes = [
            'blog' => 'Article',
            'comment' => 'Note',
            'messages' => 'Note',
            'river' => 'Note',
            'thewire' => 'Note',
            'event' => 'Event',
            'poll' => 'Page',
            'album' => 'Page',
            'photo' => 'Image',
            'topic' => 'Page',
            'topic_post' => 'Article',
        ];

        foreach ($subtypes as $s => $ap) {
            if ($s === $subtype) {
                return $ap;
            }
        }

        // Files
        if ($subtype === 'file') {
            $simpletype = $entity->getSimpleType();

            if ($simpletype === 'audio') {
                return 'Audio';
            } elseif ($simpletype === 'video') {
                return 'Video';
            } elseif ($simpletype === 'image') {
                return 'Image';
            } elseif ($simpletype === 'document') {
                return 'Document';
            }
        }

        // Dynamic types
        if (in_array($subtype, $this->getDynamicSubTypes())) {
            return $this->getDynamicApTypes($subtype);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getActivityPubActorType(\ElggGroup|\ElggSite|\ElggUser $entity)
    {
        $actor = match ($entity->getType()) {
            'user' => 'Person',
            'site' => 'Application',
            'group' => 'Group'
        };

        return $actor;
    }

    /**
     * {@inheritdoc}
     */
    public function getActorProfileData(\ElggUser|\ElggGroup $entity): array
    {
        $options = [
            'parse_emails' => true,
            'parse_hashtags' => true,
            'parse_urls' => true,
            'parse_usernames' => true,
            'parse_groups' => true,
            'parse_mentions' => true,
            'oembed' => false,
            'sanitize' => true,
            'autop' => true,
        ];

        if (elgg_is_active_plugin('theme')) {
            $svc = elgg()->{'posts.model'};
            $fields = $svc->getFields($entity, \wZm\Fields\Field::CONTEXT_PROFILE);
            $data = [];
            foreach ($fields as $field) {
                if (in_array($field->name, ['description', 'briefdescription'])) {
                    continue;
                }

                $value = $field->export($entity);
                if (elgg_is_empty($value)) {
                    continue;
                }

                if (is_array($value)) {
                    $value = implode(', ', $value);
                }

                $value = _elgg_services()->html_formatter->formatBlock($value, $options);

                $data[] = [
                    'type' => 'PropertyValue',
                    'name' => $field->label($entity),
                    'value' => $value,
                ];
            }
        } else {
            // group
            if ($entity instanceof \ElggGroup) {
                return $this->getGroupProfileData($entity);
            }

            // user
            $fields = elgg()->fields->get('user', 'user');

            $data = [];
            foreach ($fields as $field) {
                $shortname = $field['name'];
                if (in_array($shortname, ['description', 'briefdescription'])) {
                    continue;
                }

                $value = $entity->getProfileData($shortname);
                if (elgg_is_empty($value)) {
                    continue;
                }

                if (is_array($value)) {
                    $value = implode(', ', $value);
                }

                $value = _elgg_services()->html_formatter->formatBlock($value, $options);

                $data[] = [
                    'type' => 'PropertyValue',
                    'name' => elgg_echo("profile:{$shortname}"),
                    'value' => $value,
                ];
            }
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getGroupProfileData(\ElggGroup $entity)
    {
        $fields = elgg()->fields->get('group', 'group');
        $data = [];
        foreach ($fields as $field) {
            $shortname = $field['name'];
            if (in_array($shortname, ['name', 'description', 'briefdescription'])) {
                continue;
            }

            $value = $entity->$shortname;
            if (elgg_is_empty($value)) {
                continue;
            }

            if (is_array($value)) {
                $value = implode(', ', $value);
            }

            $data[] = [
                'type' => 'PropertyValue',
                'name' => elgg_echo("profile:{$shortname}"),
                'value' => $value,
            ];
        }

        return $data;
    }

    /**
     * Helper to normalize string input
     *
     * @param string $domains
     *
     * @return array
     */
    public function getDomains($domains): array
    {
        if (empty($domains) || !$domains) {
            return [];
        }

        $normalize = function ($url) {
            $url = trim($url);
            $domain = parse_url($url, PHP_URL_HOST);
            if (!$domain) {
                $domain = $url;
            }
            $domain = str_replace('www.', '', $domain);
            return $domain;
        };

        if (is_string($domains)) {
            $domains = preg_split('/$\R?^/m', $domains);
        }

        $domains = array_filter($domains);

        $domains = array_map($normalize, $domains);

        if (is_string($domains)) {
            $domains = [$domains];
        }

        return $domains;
    }

    /**
     * Returns whether the actor is whitelisted, blocked or not.
     *
     * @param \ElggUser|\ElggGroup $entity
     * @param string               $actor
     *
     * @return bool
     */
    public function checkDomain(\ElggUser|\ElggGroup $entity, string $actor): bool
    {
        $globalWhitelist = $this->getGlobalWhitelistedDomains();
        $globalBlacklist = $this->getGlobalBlockedDomains();

        $entityWhitelist = $this->getEntityWhitelistedDomains($entity);
        $entityBlacklist = $this->getEntityBlockedDomains($entity);

        $host = parse_url($actor, PHP_URL_HOST);

        if (!empty($globalWhitelist)) {
            if (in_array($host, $globalWhitelist)) {
                return true;
            }
        }

        if (!empty($globalBlacklist)) {
            if (in_array($host, $globalBlacklist)) {
                return false;
            }
        }

        if (!empty($entityWhitelist)) {
            if (in_array($host, $entityWhitelist)) {
                return true;
            }
        }

        if (!empty($entityBlacklist)) {
            if (in_array($host, $entityBlacklist)) {
                return false;
            }
        }

        if (!empty($globalWhitelist)) {
            return false;
        }

        return true;
    }

    /**
     * Returns an array of normalized global whitelisted domains on the app interactions
     *
     * @return array
     */
    public function getGlobalWhitelistedDomains(): array
    {
        $domains = (string) elgg_get_plugin_setting('activitypub_global_whitelisted_domains', 'activitypub');

        return $this->getDomains($domains);
    }

    /**
     * Returns an array of normalized global blocked domains on the app interactions
     *
     * @return array
     */
    public function getGlobalBlockedDomains(): array
    {
        $domains = (string) elgg_get_plugin_setting('activitypub_global_blocked_domains', 'activitypub');

        return $this->getDomains($domains);
    }

    /**
     * Returns an array of normalized entity whitelisted domains
     *
     * @param \ElggUser|\ElggGroup $entity
     *
     * @return array
     */
    public function getEntityWhitelistedDomains(\ElggUser|\ElggGroup $entity): array
    {
        if ($entity instanceof \ElggUser) {
            $domains = (string) $entity->getPluginSetting('activitypub', 'activitypub_whitelisted_domains');
        } else {
            $domains = (string) $entity->activitypub_whitelisted_domains;
        }

        return $this->getDomains($domains);
    }

    /**
     * Returns an array of normalized entity blocked domains
     *
     * @param \ElggUser|\ElggGroup $entity
     *
     * @return array
     */
    public function getEntityBlockedDomains(\ElggUser|\ElggGroup $entity): array
    {
        if ($entity instanceof \ElggUser) {
            $domains = (string) $entity->getPluginSetting('activitypub', 'activitypub_blocked_domains');
        } else {
            $domains = (string) $entity->activitypub_blocked_domains;
        }

        return $this->getDomains($domains);
    }

    /**
     * Returns whether the actor is blocked or not.
     *
     * @param string  $actor
     *
     * @return bool
     */
    public function domainIsGlobalBlocked($actor): bool
    {
        $domains = $this->getGlobalBlockedDomains();

        $host = parse_url($actor, PHP_URL_HOST);

        if (in_array($host, $domains)) {
            return true;
        }

        return false;
    }

    /**
     * Build audience for an activity.
     *
     * @param $to
     * @param $cc
     * @param $mention
     * @param Elgg\ActivityPub\Entity\ActivityPubActivity $activity
     */
    public function buildAudience(ActivityPubActivity $activity)
    {
        if ($activity->isUnlisted()) {
            return [];
        }

        $to = $cc = $mention = $target = [];

        if (!empty($activity->getTo())) {
            $to = $activity->getTo();
        }

        if ($activity->isPublic()) {
            array_unshift($to, ActivityPubActivity::PUBLIC_URL);
        }

        $owner = $activity->getOwnerEntity();

        if ($activity->isPublic() || $activity->isFollowers()) {
            if ($owner instanceof \ElggGroup) {
                $followers_url = elgg_generate_url('view:activitypub:group:followers', [
                    'guid' => (int) $activity->owner_guid,
                ]);
            } else {
                $followers_url = elgg_generate_url('view:activitypub:user:followers', [
                    'guid' => (int) $activity->owner_guid,
                ]);
            }

            if ($activity->isPublic()) {
                if ($owner instanceof \ElggGroup) {
                    $to[] = elgg_generate_url('view:activitypub:group', [
                        'guid' => (int) $activity->owner_guid,
                    ]);

                    /*
                    if ($activity->getActivityType() === 'Create') {
                        $target = elgg_generate_url('view:activitypub:group', [
                            'guid' => (int) $owner->guid,
                        ]);
                    }
                    */
                }

                $cc = [$followers_url];
            } else {
                $to = [$followers_url];
            }
        }

        if (!empty($to)) {
            foreach ($to as $href) {
                if ($href === ActivityPubActivity::PUBLIC_URL) {
                    continue;
                }

                $parsed = parse_url($href);
                $username = $this->getActorUsername($href);
                $name = '@' . $username . '@' . $parsed['host'];

                $mention[] = [
                    'href' => $href,
                    'name' => $name,
                ];
            }
        }

        return ['to' => $to, 'cc' => $cc, 'mention' => $mention, 'target' => $target];
    }

    /**
     * Returns the actor's followers IDs.
     *
     * @param $actor
     *
     * @return array
     */
    public function getFollowersIds(\ElggUser|\ElggGroup $actor): array
    {
        $items = [];

        $activity_object = $actor instanceof \ElggUser ? elgg_generate_url('view:activitypub:user', [
            'guid' => (int) $actor->guid,
        ]) : elgg_generate_url('view:activitypub:group', [
            'guid' => (int) $actor->guid,
        ]);

        $options = [
            'types' => 'object',
            'subtypes' => ActivityPubActivity::SUBTYPE,
            'owner_guid' => (int)  $actor->guid,
            'metadata_name_value_pairs' => [
                [
                    'name' => 'status',
                    'value' => 1,
                ],
                [
                    'name' => 'processed',
                    'value' => 1,
                ],
                [
                    'name' => 'activity_object',
                    'value' => $activity_object,
                ],
                [
                    'name' => 'collection',
                    'value' => ActivityPubActivity::INBOX,
                ],
                [
                    'name' => 'activity_type',
                    'value' => ['Follow', 'Join'],
                ],
            ],
            'limit' => false,
            'batch' => true,
            'batch_size' => 50,
            'batch_inc_offset' => false,
        ];

        $activities = elgg_call(ELGG_IGNORE_ACCESS, function () use ($options) {
            return elgg_get_entities($options);
        });

        foreach ($activities as $activity) {
            $items[] = $activity->getActor();
        }

        return $items;
    }

    public function http_client($options = [])
    {
        $jar = \GuzzleHttp\Cookie\CookieJar::fromArray(
            [
                elgg_get_site_entity()->getDisplayName() => elgg_get_session()->getID()
            ],
            elgg_get_site_entity()->getDomain()
        );

        $config = [
            'verify' => true,
            'timeout' => 30,
            'connect_timeout' => 5,
            'read_timeout' => 5,
            'cookies' => $jar,
        ];
        $config = $config + $options;

        return new Client($config);
    }

    /**
     * Returns the actor's username
     *
     * @param $handle Account handle in formats: user@domain.app, @user@domain.app
     *
     * @return string
     */
    public function extractUsername(string $handle)
    {
        $pattern = '/(?:\@)?([^\@]+)/';

        if (preg_match($pattern, $handle, $matches)) {
            return $matches[1];
        } else {
            return $handle;
        }
    }

    /**
     * Returns the actor's domain
     *
     * @param $handle Account handle in formats: user@domain.app, @user@domain.app
     *
     * @return string
     */
    public function extractDomain(string $handle)
    {
        $pattern = '/@?[^@]+@([^\s@]+)/';

        if (preg_match($pattern, $handle, $matches)) {
            return $matches[1];
        } else {
            return $handle;
        }
    }

    /**
     * Returns the actor's username
     *
     * @param $url Account URL
     *
     * @return string
     */
    public function getActorUsername(string $url)
    {
        $data = \Elgg\ActivityPub\Services\ResolveService::getRemoteObject($url);

        if (empty($data)) {
            return $this->getActorNameFromUrl($url);
        }

        return (string) $data['preferredUsername'];
    }

    /**
     * Returns the actor's name
     *
     * @param $url Account URL
     *
     * @return string
     */
    public function getActorName(string $url)
    {
        $data = \Elgg\ActivityPub\Services\ResolveService::getRemoteObject($url);

        if (empty($data)) {
            return $this->getActorNameFromUrl($url);
        }

        return (string) $data['name'];
    }

    public function getActorNameFromUrl(string $url)
    {
        // check if domain is banned.
        if ((bool) $this->domainIsGlobalBlocked($url)) {
            return null;
        }

        return (string) preg_replace('/[^A-Za-z0-9]/', '', basename($url));
    }

    /**
     * Returns the actor's domain
     *
     * @param $url Account URL
     *
     * @return string
     */
    public function getActorDomain(string $url)
    {
        // check if domain is banned.
        if ((bool) $this->domainIsGlobalBlocked($url)) {
            return null;
        }

        $path = parse_url($url);
        return (string) $path['host'];
    }

    public function getActorHandle(string $url)
    {
        // check if domain is banned.
        if ((bool) $this->domainIsGlobalBlocked($url)) {
            return null;
        }

        return (string) $this->getActorNameFromUrl($url) . '@' . $this->getActorDomain($url);
    }

    /**
     * Returns the actor's icon URL
     *
     * @param $url Account URL
     *
     * @return string
     */
    public function getActorIcon(string $url)
    {
        $data = \Elgg\ActivityPub\Services\ResolveService::getRemoteObject($url);

        if (empty($data)) {
            return null;
        }

        $avatar = (array) $data['icon'] ?? false;
        if (!empty($avatar)) {
            return (string) $avatar['url'];
        }

        return null;
    }

    /**
     * Get a FederatedUser by username
     */
    public function getFederatedUserByUsername(string $username): ?FederatedUser
    {
        if (empty($username)) {
            return null;
        }

        // Fixes #6052. Username is frequently sniffed from the path info, which,
        // unlike $_GET, is not URL decoded. If the username was not URL encoded,
        // this is harmless.
        $username = rawurldecode($username);
        if (empty($username)) {
            return null;
        }

        $users = elgg_get_entities([
            'types' => 'user',
            'subtypes' => 'federated',
            'metadata_name_value_pairs' => [
                [
                    'name' => 'username',
                    'value' => $username,
                    'case_sensitive' => false,
                ],
            ],
            'limit' => 1,
        ]);

        return $users ? $users[0] : null;
    }

    public function isRemoteFollow(string $object, string $actor): bool
    {
        $activities = elgg_call(ELGG_IGNORE_ACCESS, function () use ($object, $actor) {
            return elgg_get_entities([
                'type' => 'object',
                'subtype' => ActivityPubActivity::SUBTYPE,
                'metadata_name_value_pairs' => [
                    [
                        'name' => 'activity_object',
                        'value' => $object,
                    ],
                    [
                        'name' => 'actor',
                        'value' => $actor,
                    ],
                    [
                        'name' => 'activity_type',
                        'value' => ['Follow', 'Join'],
                    ],
                    [
                        'name' => 'collection',
                        'value' => ActivityPubActivity::OUTBOX,
                    ],
                    [
                        'name' => 'processed',
                        'value' => 0,
                    ],
                    [
                        'name' => 'queued',
                        'value' => 1,
                    ],
                ],
                'limit' => 1,
            ]);
        });

        if (!empty($activities)) {
            return true;
        }

        return false;
    }
}
