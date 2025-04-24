<?php

namespace Elgg\ActivityPub\Services;

use DateTime;
use Elgg\Traits\Di\ServiceFacade;
use GuzzleHttp;
use GuzzleHttp\Psr7\Request;
use HttpSignatures\Context;
use Psr\Http\Message\ResponseInterface;

class ActivityPubClient
{
    use ServiceFacade;

    /** @var string[] HttpSignature Private Key */
    private array $privateKeys;

    protected $httpClient;

    public function __construct(
        \GuzzleHttp\Client $httpClient
    ) {
        $this->httpClient = $httpClient;
    }

    /**
     * Returns registered service name
     * @return string
     */
    public static function name()
    {
        return 'activityPubClient';
    }

    /**
     * {@inheritdoc}
     */
    public function __get($name)
    {
        return $this->$name;
    }

    public function withPrivateKeys(array $privateKeys): Client
    {
        $instance = clone $this;
        $instance->privateKeys = $privateKeys;
        return $instance;
    }

    /**
     * @param string $method
     * @param string $url
     * @param array $body
     * @return ResponseInterface
     */
    public function request(string $method, string $url, array $body = []): ResponseInterface
    {
        $parsed = parse_url($url);
        $host = $parsed['host'];

        $request = new Request(
            method: $method,
            uri: $url,
            headers: [
                'Host' => $host,
                'Date' => (new DateTime())->format('D, d M Y H:i:s \G\M\T'),
                'Content-Type' => 'application/activity+json; charset=utf-8',
                'Accept' => 'application/activity+json, application/ld+json; profile="https://www.w3.org/ns/activitystreams"',
            ],
            body: json_encode($body),
        );

        if (!isset($this->privateKeys)) {
            $privateKey = elgg()->activityPubSignature->getPrivateKey((string) elgg_get_site_entity()->getDomain());
            $this->privateKeys = [
                elgg_generate_url('view:activitypub:application') . '#main-key' => (string) $privateKey,
            ];
        }

        $context = new Context([
            'keys' => $this->privateKeys,
            'algorithm' => 'rsa-sha256',
            'headers' => ['(request-target)', 'Host', 'Date', 'Accept'],
        ]);

        $request = $context->signer()->signWithDigest($request);

        $jar = \GuzzleHttp\Cookie\CookieJar::fromArray(
            [
                (string) elgg_get_site_entity()->getDisplayName() => elgg_get_session()->getID()
            ],
            (string) elgg_get_site_entity()->getDomain()
        );

        $opts = [
            'verify' => true,
            'timeout' => 30,
            'connect_timeout' => 5,
            'read_timeout' => 5,
            'cookies' => $jar,
        ];

        if ($httpProxy = (array) _elgg_services()->config->proxy) {
            $opts['proxy'] = $httpProxy;
        }

        $json = $this->httpClient->send($request, $opts);

        return $json;
    }
}
