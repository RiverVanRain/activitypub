<?php

/**
 * WebFinger
 * @author Nikolai Shcherbin
 * @license GNU Affero General Public License version 3
 * @copyright (c) Nikolai Shcherbin 2024
 * @link https://wzm.me
**/

namespace Elgg\ActivityPub\WebFinger\Controller;

use Elgg\Exceptions\Http\BadRequestException;
use Elgg\Http\Request;

/**
 * Parses Webfinger parameters from a request.
 */

class WebfingerParameters
{
    /**
     * The name of a host being requested.
     *
     * @var string
     */
    const HOST_KEY_NAME = 'host';

    /**
     * The name of an account being requested.
     *
     * @var string
     */
    const ACCOUNT_KEY_NAME = 'account';

    /**
     * The rel value.
     *
     * @var string
     */
    const REL_KEY_NAME = 'rel';

    /**
     * The current request.
     *
     * @var \Elgg\Http\Request
     */
    protected $request;

    /**
     * Sets the request.
     *
     * @param \Elgg\Request $request
     *   The current request.
     *
     * @return $this
     *   The called Webfinger parameters object.
     */
    public function setRequest(Request $request): self
    {
        $this->request = $request;
        return $this;
    }

    /**
     * Extracts Webfinger query parameters from the request.
     *
     * @return array
     *   An array of parameters.
     */
    public function getParams(): array
    {
        $params = [];

        if ($this->request->getParam('resource')) {
            $resource = $this->request->getParam('resource') ?? null;

            if (!$resource) {
                throw new BadRequestException(elgg_echo('activitypub:webfinger:resource:empty'));
            }

            // @todo is HTTP_HOST the right value to check here?
            $host = $this->request->getParam('host');

            // Convert URL if needed into a form for which 'user' and 'host' are parsed.
            if (strpos($resource, '//') === false) {
                $resource = str_replace(':', '://', $resource);
            }

            // Allow discovery without acct: scheme.
            if (strpos($resource, 'acct:') === false) {
                $resource = 'acct://' . $resource;
            }

            $url = parse_url($resource);

            if (isset($url['scheme']) && isset($url['user']) && isset($url['host'])) {
                $params[static::HOST_KEY_NAME] = $host;

                switch ($url['scheme']) {
                    case 'acct':
                    // Ensure the request is for this domain.
                    // @todo support comparison of subdomain?
                        if ($host === $url['host']) {
                            $params[static::ACCOUNT_KEY_NAME] = $url['user'];
                        } else {
                            elgg_log(elgg_echo('activitypub:webfinger:resource:no_match', [$url['host'], $host]), \Psr\Log\LogLevel::ERROR);
                        }
                        break;
                }
            }
        }

        if ($this->request->getParam('rel')) {
            // Standardize as an array.
            $params[static::REL_KEY_NAME] = (array) $this->request->getParam('rel');
        }

        return $params;
    }
}
