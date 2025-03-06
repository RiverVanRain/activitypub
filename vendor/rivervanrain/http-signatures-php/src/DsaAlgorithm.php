<?php

namespace HttpSignatures;

use phpseclib3\Crypt\PublicKeyLoader;

class DsaAlgorithm implements AlgorithmInterface
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
        return sprintf('dsa-%s', $this->digestName);
    }

    /**
     * @param string $key
     * @param string $data
     *
     * @return string
     *
     * @throws \HttpSignatures\AlgorithmException
     */
    public function sign($signingKey, $data)
    {
        $dsa = PublicKeyLoader::load($signingKey)
        ->withHash($this->digestName);
        $signature = $dsa->sign($data);

        return $signature;
    }

    public function verify($message, $signature, $verifyingKey)
    {
        $ec = PublicKeyLoader::load($verifyingKey)
          ->withHash($this->digestName);
        try {
            $valid = $ec->verify($message, base64_decode($signature));

            return $valid;
        } catch (\Exception $e) {
            // Tolerate malformed signature
            return false;
        }
    }
}
