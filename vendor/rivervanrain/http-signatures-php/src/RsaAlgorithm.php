<?php

namespace HttpSignatures;

use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;

class RsaAlgorithm implements AlgorithmInterface
{
    /** @var string */
    private $digestName;

    /**
     * @param string $digestName
     */
    public function __construct($digestName)
    {
        $this->digestName = $digestName;
    }

    /**
     * @return string
     */
    public function name()
    {
        return sprintf('rsa-%s', $this->digestName);
    }

    /**
     * @param string $key
     * @param string $data
     *
     * @return string
     */
    public function sign($signingKey, $data)
    {
        $rsa = PublicKeyLoader::load($signingKey)
          ->withHash($this->digestName)
          ->withPadding(RSA::SIGNATURE_PKCS1);
        $signature = $rsa->sign($data);

        return $signature;
    }

    public function verify($message, $signature, $verifyingKey)
    {
        $rsa = PublicKeyLoader::load($verifyingKey)
          ->withHash($this->digestName)
          ->withPadding(RSA::SIGNATURE_PKCS1);
        try {
            $valid = $rsa->verify($message, base64_decode($signature));

            return $valid;
        } catch (\Exception $e) {
            if ('Invalid signature' != $e->getMessage()) {
                // Unhandled error state
                throw $e;
            } else {
                // Tolerate malformed signature
                return false;
            }
        }
    }
}
