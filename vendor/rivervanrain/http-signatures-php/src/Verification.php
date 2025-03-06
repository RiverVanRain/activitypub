<?php

namespace HttpSignatures;

use Psr\Http\Message\RequestInterface;

class Verification
{
    /** @var RequestInterface */
    private $message;

    /** @var KeyStoreInterface */
    private $keyStore;

    /** @var string */
    private $header;

    /** @var array */
    private $parameters;

    /**
     * @param RequestInterface $message
     */
    public function __construct($message, KeyStoreInterface $keyStore, $header)
    {
        $this->message = $message;
        $this->keyStore = $keyStore;

        // TODO: Find one signature line within multiple header instances
        // This will permit e.g. Authorization: Bearer to co-exist with Authorization: Signature
        switch (strtolower($header)) {
            case 'signature':
                if (0 == sizeof($message->getHeader('Signature'))) {
                    throw new HeaderException("Cannot locate header 'Signature'");
                } elseif (sizeof($message->getHeader('Signature')) > 1) {
                    throw new HeaderException("Multiple headers named 'Signature'");
                }
                $signatureLine = $message->getHeader('Signature')[0];
                break;
            case 'authorization':
            if (0 == sizeof($message->getHeader('Authorization'))) {
                throw new HeaderException("Cannot locate header 'Authorization'");
            } elseif (sizeof($message->getHeader('Authorization')) > 1) {
                throw new HeaderException("Multiple headers named 'Authorization'");
            }
                $authorizationType = explode(' ', $message->getHeader('Authorization')[0])[0];
                if ('Signature' == $authorizationType) {
                    $signatureLine = substr($message->getHeader('Authorization')[0], strlen('Signature '));
                } else {
                    throw new HeaderException("Unknown Authorization type $authorizationType, cannot verify");
                }
                break;
            default:
                throw new HeaderException("Unknown header type '".$header."', cannot verify");
                break;
        }
        $signatureParametersParser = new SignatureParametersParser(
          $signatureLine
        );
        $this->parameters = $signatureParametersParser->parse();
    }

    /**
     * @return bool
     */
    public function verify()
    {
        try {
            $key = $this->key();
            switch ($key->getClass()) {
                case 'secret':
                  if (hash_equals(
                    $this->expectedSignature()->string(),
                    $this->providedSignature()
                    )) {
                      return true;
                  } else {
                      throw new SignatureException('Invalid signature', 1);
                  }
                  break;
                case 'asymmetric':
                    $signedString = new SigningString(
                        $this->headerList(),
                        $this->message
                    );
                    $hashAlgo = explode('-', $this->parameter('algorithm'))[1];
                    $algorithm = Algorithm::create($this->parameter('algorithm'));
                    $result = $algorithm->verify(
                        $signedString->string(),
                        $this->parameter('signature'),
                        $key->getVerifyingKey());

                    return $result;
                default:
                    throw new Exception("Unknown key type '".$key->getType()."', cannot verify");
            }
            // } catch (SignatureParseException $e) {
        //     return false;
        } catch (KeyStoreException $e) {
            throw new KeyStoreException("Cannot locate key for supplied keyId '{$this->parameter('keyId')}'", 1);
            // return false;
            // } catch (SignedHeaderNotPresentException $e) {
        //     return false;
        }
    }

    /**
     * @return Signature
     */
    private function expectedSignature()
    {
        return new Signature(
            $this->message,
            $this->keyId(),
            $this->algorithm(),
            $this->headerList()
        );
    }

    /**
     * @return string
     */
    private function providedSignature()
    {
        return base64_decode($this->headerParameter('signature'));
    }

    /**
     * @return Key
     *
     * @throws Exception
     */
    private function keyId()
    {
        return $this->keyStore->fetch($this->headerParameter('keyId'));
    }

    /**
     * @return Algorithm
     *
     * @throws Exception
     */
    private function algorithm()
    {
        return Algorithm::create($this->headerParameter('algorithm'));
    }

    /**
     * @return HeaderList
     */
    private function signatureHeaderList()
    {
        return HeaderList::fromString($this->signatureHeaderParameter('headers'));
    }

    /**
     * @param string $name
     *
     * @return string
     *
     * @throws Exception
     */
    private function headerParameter($name)
    {
        // $headerParameters = $this->headerParameters();
        if (!isset($this->parameters[$name])) {
            throw new Exception("'$this->header' header parameters does not contain '$name'");
        }

        return $this->parameters[$name];
    }

    /**
     * @return string
     */
    private function signatureHeaderValue($headerName)
    {
        $headerLine = $this->fetchHeader($headerName);
        switch ($headerName) {
            case 'Authorization':
                return substr($headerLine, strlen('Signature '));
                break;
            case 'Signature':
                return $headerLine;
                break;
        }
    }

    /**
     * @param $name
     *
     * @return string|null
     */
    private function fetchHeader($name)
    {
        // grab the most recently set header.
        $header = $this->message->getHeader($name);

        return end($header);
    }

    /**
     * @return Key
     */
    private function key()
    {
        return $this->keyStore->fetch($this->parameter('keyId'));
    }

    /**
     * @param string $name
     *
     * @return string
     *
     * @throws Exception
     */
    private function parameter($name)
    {
        // $parameters = $this->parameters();
        if (!isset($this->parameters[$name])) {
            if ('headers' == $name) {
                return 'date';
            } else {
                throw new Exception("Signature parameters does not contain '$name'");
            }
        }

        return $this->parameters[$name];
    }

    /**
     * @return string
     *
     * @throws Exception
     */
    private function header()
    {
        switch ($this->header) {
          case 'Signature':
            return $this->fetchHeader('Signature');
            break;
          case 'Authorization':
            return substr($authorization, strlen('Signature '));
            break;
        }
    }

    /**
     * @return HeaderList
     */
    private function headerList()
    {
        return HeaderList::fromString($this->parameter('headers'));
    }

    public function getSigningString()
    {
        $signedString = new SigningString(
          $this->headerList(),
          $this->message
      );

        return $signedString->string();
    }
}
