<?php

namespace Elgg\ActivityPub\Services;

use Elgg\ActivityPub\Entity\ActivityPubActivity;
use Elgg\Exceptions\Http\BadRequestException;
use Laminas\Validator\EmailAddress as EmailAddressValidator;
use Laminas\Validator\Hostname;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

/**
 * Provides the resolve service.
 */
class ResolveService
{
    /**
     * Resolves a uri to valid status or actor.
     */
    public static function getRemoteObject(string $uri)
    {
        if (is_array($uri)) {
            if (array_key_exists('id', $uri)) {
                $url = $uri['id'];
            } elseif (array_key_exists('url', $uri)) {
                $url = $uri['url'];
            } else {
                if ((bool) elgg_get_plugin_setting('log_general_inbox_error', 'activitypub')) {
                    self::log(elgg_echo('activitypub:resolve:service:invalid_url', [$url]));
                }
                throw new BadRequestException();
            }
        } else {
            $url = (string) $uri;
        }

        if (preg_match('/^@?' . ActivityPubActivity::USERNAME_REGEXP . '$/i', $url) || elgg_is_valid_email($url)) {
            try {
                $server = elgg()->activityPubUtility->getServer();
                // Get actor.
                $actor = $server->actor($url);
                // Get a WebFinger instance.
                $webfinger = $actor->webfinger();
                if ($webfinger) {
                    $url = $webfinger->getProfileId();

                    // check if domain is banned.
                    if ((bool) elgg()->activityPubUtility->domainIsGlobalBlocked($url)) {
                        if ((bool) elgg_get_plugin_setting('log_general_inbox_error', 'activitypub')) {
                            self::log(elgg_echo('activitypub:resolve:service:domain_blocked', [$url]));
                        }

                        throw new BadRequestException();
                    }
                }
            } catch (\Exception $e) {
                throw new BadRequestException($e->getMessage());
            }
        }

        // Now let's look to allowed protocols to get objects from (with some dreaming of distributed networks too)
        try {
            $validator = new UrlValidator();

            if ($validator->isValidUrl($url)) {
                try {
                    $content = elgg()->activityPubClient->request('GET', $url)->getBody();

                    if ($content) {
                        return json_decode($content, true);
                    }
                } catch (\Exception $e) {
                    if ((bool) elgg_get_plugin_setting('log_general_inbox_error', 'activitypub')) {
                        self::log(elgg_echo('activitypub:resolve:service:bad_response', [$url]));
                    }

                    throw new BadRequestException($e->getMessage());
                }
            }
        } catch (\Exception $e) {
            if ((bool) elgg_get_plugin_setting('log_general_inbox_error', 'activitypub')) {
                self::log(elgg_echo('activitypub:resolve:service:invalid_url', [$url]));
            }

            throw new BadRequestException($e->getMessage());
        }
    }

    /**
     * Resolves a uri to valid status or actor.
     */
    public static function resolveQuery(string $uri)
    {
        $default = [
            'accounts' => [],
            'statuses' => [],
        ];

        $json = self::getRemoteObject($uri);

        if (!$json || !isset($json['@context']) || !isset($json['type']) || !in_array($json['type'], ['Person', 'Group', 'Article', 'Note', 'Page', 'Event'])) {
            return $default;
        }

        switch ($json['type']) {
            case 'Article':
            case 'Event':
            case 'Note':
            case 'Page':
                $default['statuses'][] = $json;
                break;

            case 'Person':
            case 'Group':
                $default['accounts'][] = $json;
                break;
        }

        return $default;
    }

    /** Logger */
    public static function log($message = '')
    {
        $log_file = elgg_get_data_path() . 'activitypub/logs/log_general_inbox_error';

        $log = new Logger('ActivityPub');
        $log->pushHandler(new StreamHandler($log_file, Logger::WARNING));

        // add records to the log
        return $log->warning($message);
    }

    public static function httpClient($options = [])
    {
        $config = [
            'verify' => true,
            'timeout' => 10,
        ];
        $config = $config + $options;

        return new Client($config);
    }
}
