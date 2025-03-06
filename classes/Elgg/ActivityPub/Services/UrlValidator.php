<?php

namespace Elgg\ActivityPub\Services;

/**
 * URL helper service.
 */
class UrlValidator {
    private const ALLOWED_PROTOCOLS = ['http', 'https', 'did'];
    
    private const URL_PATTERN = 
        '/^(?:(https?:\/\/|did:))' . // protocol
        '(?:(?:[a-zA-Z0-9\-._~!$&\'()*+,;=:]|%[0-9a-fA-F]{2})*@)?' . // authentication
        '(?:[a-zA-Z0-9\-._~!$&\'()*+,;=]|%[0-9a-fA-F]{2})+' . // domain
        '(?::\d*)?' . // port
        '(?:\/(?:[a-zA-Z0-9\-._~!$&\'()*+,;=:@\/]|%[0-9a-fA-F]{2})*)?'. // path
        '(?:\?(?:[a-zA-Z0-9\-._~!$&\'()*+,;=:@\/?]|%[0-9a-fA-F]{2})*)?' . // query parametrs
        '(?:#(?:[a-zA-Z0-9\-._~!$&\'()*+,;=:@\/?]|%[0-9a-fA-F]{2})*)?$/i'; // fragment

    /**
     * Checks URL for protocol compliance and correctness
     *
     * @param string $url
     * @return bool
     */
    public function isValidUrl(string $url): bool {
        if (empty($url)) {
            return false;
        }

        // Basic Regular Expression Validation
        if (!preg_match(self::URL_PATTERN, $url)) {
            return false;
        }

        // Protocol check
        $parsedUrl = parse_url($url);
        if (!isset($parsedUrl['scheme'])) {
            return false;
        }

        return in_array($parsedUrl['scheme'], self::ALLOWED_PROTOCOLS, true);
    }

    /**
     * Gets the protocol from the URL
     *
     * @param string $url
     * @return string|null
     */
    public function getProtocol(string $url): ?string {
        $parsedUrl = parse_url($url);
        return $parsedUrl['scheme'] ?? null;
    }
}
