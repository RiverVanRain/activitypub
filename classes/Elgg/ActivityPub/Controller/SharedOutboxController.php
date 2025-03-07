<?php

/**
 * ActivityPub
 * @author Nikolai Shcherbin
 * @license GNU Affero General Public License version 3
 * @copyright (c) Nikolai Shcherbin 2024
 * @link https://wzm.me
**/

namespace Elgg\ActivityPub\Controller;

use Elgg\ActivityPub\Entity\ActivityPubActivity;

class SharedOutboxController
{
    /**
     * Application outbox routing callback.
     */
    public function __invoke(\Elgg\Request $request): \Elgg\Http\Response
    {
        $url = (string) elgg_generate_url('view:activitypub:outbox');

        $data = [
            '@context' => ActivityPubActivity::CONTEXT_URL,
            'id' => $url,
            'type' => 'OrderedCollection',
            'first' => elgg_http_add_url_query_elements($url, [
                'page' => 'true',
                'offset' => 0,
            ]),
            'last' => elgg_http_add_url_query_elements($url, [
                'page' => 'true',
            ]),
            'totalItems' => 0,
        ];

        try {
            $response = new \Elgg\Http\OkResponse();

            $response->setHeaders([
                'Accept' => 'application/activity+json, application/ld+json; profile="https://www.w3.org/ns/activitystreams"',
                'Content-Type' => 'application/activity+json; charset=utf-8',
            ]);

            $response->setContent(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        } catch (\Exception $e) {
            elgg_log('Outbox routing callback error: ' . $e->getMessage(), \Psr\Log\LogLevel::ERROR);
            $response = new \Elgg\Http\ErrorResponse('', 400);
        }

        return $response;
    }
}
