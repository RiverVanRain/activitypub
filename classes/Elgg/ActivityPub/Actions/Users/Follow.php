<?php

namespace Elgg\ActivityPub\Actions\Users;

use Elgg\ActivityPub\Entity\ActivityPubActivity;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class Follow
{
    public function __invoke(\Elgg\Request $request)
    {
        $handle = (string) $request->getParam('handle');

        if (!$handle) {
            return elgg_error_response(elgg_echo('activitypub:user:follow:error:handle'));
        }

        $user = get_user((int) $request->getParam('guid'));

        $local_actor = (string) $request->getParam('local_actor');

        if (!$local_actor || !$user instanceof \ElggUser || !(bool) elgg()->activityPubUtility->isEnabledUser($user)) {
            return elgg_error_response(elgg_echo('activitypub:user:follow:error:local_actor'));
        }

        $endpoint = false;
        $webfinger = false;

        try {
            // acct:elgg@indieweb.social
            if (str_starts_with($handle, 'acct:')) {
                $webfinger = elgg()->webfingerService->get($handle);
            } elseif (preg_match('/^@?' . ActivityPubActivity::USERNAME_REGEXP . '$/i', $handle) || elgg_is_valid_email($handle)) {
                // elgg@indieweb.social
                $webfinger = elgg()->webfingerService->get('acct:' . $handle);
            } else {
                // https://indieweb.social/users/elgg | https://indieweb.social/@elgg
                $data = \Elgg\ActivityPub\Services\ResolveService::getRemoteObject($handle);
                if (!elgg_is_empty($data) && null !== $data['preferredUsername']) {
                    if ($domain = elgg()->activityPubUtility->getActorDomain($handle)) {
                        $webfinger = elgg()->webfingerService->get('acct:' . (string) $data['preferredUsername'] . '@' . (string) $domain);
                    }
                }
            }

            if ($webfinger) {
                $links = $webfinger['links'];

                foreach ($links as $link) {
                    if ($link['rel'] === 'http://ostatus.org/schema/1.0/subscribe') {
                        $endpoint = $link['template'];
                    }
                }
            }
        } catch (\Exception $e) {
            if ((bool) elgg_get_plugin_setting('log_general_inbox_error', 'activitypub')) {
                $this->log(elgg_echo('activitypub:user:follow:error:endpoint', [$e->getMessage()]));
            }

            return elgg_error_response(elgg_echo('activitypub:user:follow:fail'));
        }

        if (!$endpoint) {
            if ((bool) elgg_get_plugin_setting('log_general_inbox_error', 'activitypub')) {
                $this->log(elgg_echo('activitypub:user:follow:error:endpoint', ['Endpoint is empty']));
            }

            return elgg_error_response(elgg_echo('activitypub:user:follow:fail'));
        }

        $redirect = str_replace('{uri}', $local_actor, $endpoint);

        return new \Elgg\Http\RedirectResponse($redirect);
    }

    /** Logger */
    public function log($message = '')
    {
        $log_file = elgg_get_data_path() . 'activitypub/logs/log_general_inbox_error';

        $log = new Logger('ActivityPub');
        $log->pushHandler(new StreamHandler($log_file, Logger::WARNING));

        // add records to the log
        return $log->warning($message);
    }
}
