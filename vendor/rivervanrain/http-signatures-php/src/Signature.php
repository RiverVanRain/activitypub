<?php

namespace HttpSignatures;

use Psr\Http\Message\RequestInterface;

class Signature
{
    /** @var Key */
    private $key;

    /** @var AlgorithmInterface */
    private $algorithm;

    /** @var SigningString */
    private $signingString;

    /**
     * @param RequestInterface $message
     */
    public function __construct($message, Key $key, AlgorithmInterface $algorithm, HeaderList $headerList)
    {
        $this->key = $key;
        $this->algorithm = $algorithm;
        $this->signingString = new SigningString($headerList, $message);
    }

    public function string()
    {
        return $this->algorithm->sign(
            $this->key->getSigningKey(),
            $this->signingString->string()
          );
    }
}
