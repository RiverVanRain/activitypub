<?php

namespace HttpSignatures;

class SignatureParameters
{
    /** @var Key */
    public $key;
    
    /** @var AlgorithmInterface */
    public $algorithm;
    
    /** @var HeaderList */
    public $headerList;
    
    /** @var Signature */
    public $signature;

    /**
     * @param Key                $key
     * @param AlgorithmInterface $algorithm
     * @param HeaderList         $headerList
     * @param Signature          $signature
     */
    public function __construct($key, $algorithm, $headerList, $signature)
    {
        $this->key = $key;
        $this->algorithm = $algorithm;
        $this->headerList = $headerList;
        $this->signature = $signature;
    }

    /**
     * @return string
     */
    public function string()
    {
        return implode(',', $this->parameterComponents());
    }

    /**
     * @return array
     */
    private function parameterComponents()
    {
        $components = [];
        $components[] = sprintf('keyId="%s"', $this->key->getId());
        $components[] = sprintf('algorithm="%s"', $this->algorithm->name());
        if ($this->headerList->headerListSpecified()) {
            $components[] = sprintf('headers="%s"', $this->headerList->string());
        }
        $components[] = sprintf('signature="%s"', $this->signatureBase64());

        return $components;
    }

    /**
     * @return string
     */
    private function signatureBase64()
    {
        return base64_encode($this->signature->string());
    }
}
