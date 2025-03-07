<?php

namespace Elgg\ActivityPub\WebFinger;

use Elgg\Traits\Di\ServiceFacade;

class WebfingerService
{
    use ServiceFacade;

    /**
     * Returns registered service name
     * @return string
     */
    public static function name()
    {
        return 'webfingerService';
    }

    /**
     * Fetches a webfinger resources from URI (eg. acct:elgg@indieweb.social).
     */
    public function get(string $uri): array
    {
        // Split the username
        [$_, $domain] = explode('@', $uri);
        $requestUrl = "https://{$domain}/.well-known/webfinger?resource={$uri}";
        $response = elgg()->activityPubClient->request('GET', $requestUrl);

        $json = json_decode($response->getBody()->getContents(), true);

        if (!is_array($json)) {
            throw new \Elgg\Exceptions\Http\BadRequestException(elgg_echo('BadWebfingerResponse'));
        }

        return $json;
    }

    /**
     * Get ActivityPhp profile id URL
     *
     * @return string
     */
    public function getProfileId(array $data)
    {
        $links = $data;

        if (isset($data['links'])) {
            $links = $data['links'];
        }

        if (empty($links)) {
            return null;
        }

        foreach ($links as $link) {
            if (isset($link['rel'], $link['type'], $link['href'])) {
                if (
                    $link['rel'] == 'self'
                    && ($link['type'] === 'application/activity+json' || $link['type'] === 'application/ld+json; profile="https://www.w3.org/ns/activitystreams"')
                ) {
                    return $link['href'];
                }
            }
        }
    }
}
