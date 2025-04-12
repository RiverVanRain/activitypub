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

class FollowerSynchronization
{
    /**
     * Follower synchronization callback.
     *
     * https://codeberg.org/fediverse/fep/issues/6
     * https://git.activitypub.dev/ActivityPubDev/Fediverse-Enhancement-Proposals/issues/11
     * https://docs.joinmastodon.org/spec/activitypub/#follower-synchronization-mechanism
     */
    public function __invoke(\Elgg\Request $request): \Elgg\Http\Response
    {
        $user = $request->getEntityParam();

        if (!$user instanceof \ElggUser || !(bool) elgg()->activityPubUtility->isEnabledUser($user)) {
            throw new \Elgg\Exceptions\Http\PageNotFoundException();
        }

        try {
            $referer = $request->getHttpRequest()->headers->get('referer');

            if ($referer) {
                $referrerDomain = '?domain=' . parse_url($referer, PHP_URL_HOST);
            } else {
                $referrerDomain = false;
            }

            $data = [
                '@context' => ActivityPubActivity::CONTEXT_URL,
                'type' => 'OrderedCollection',
                'id' => elgg_generate_url('view:activitypub:user:followers', [
                    'guid' => (int) $user->guid,
                ]) . $referrerDomain,
                'orderedItems' => elgg()->activityPubUtility->getFollowersIds($user),
            ];

            $response = new \Elgg\Http\OkResponse();

            $response->setHeaders([
                'Accept' => 'application/activity+json, application/ld+json; profile="https://www.w3.org/ns/activitystreams"',
                'Content-Type' => 'application/activity+json; charset=utf-8',
                'Access-Control-Allow-Origin' => '*',
            ]);

            $response->setContent(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        } catch (\Exception $e) {
            elgg_log('Followers synchronization routing callback error: ' . $e->getMessage(), \Psr\Log\LogLevel::ERROR);
            $response = new \Elgg\Http\ErrorResponse('', 400);
        }

        return $response;
    }
}
