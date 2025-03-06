<?php

namespace HttpSignatures;

use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\File\X509;

class Key
{
    /** @var string */
    private $id;

    /** @var string */
    private $secret;

    /** @var resource */
    private $certificate;

    /** @var resource */
    private $publicKey;

    /** @var resource */
    private $privateKey;

    /** @var string */
    private $type;

    /** @var string */
    public $algorithm;

    /** @var array */
    public $publicKeys;

    /** @var string */
    public $curve;

    /** @var array */
    public $secrets;

    /** @var string */
    public $class;

    /**
     * @param string       $id
     * @param string|array $secret
     */
    public function __construct($id, $keys)
    {
        if (empty($keys)) {
            throw new KeyException('No Key(s) provided', 1);
        }
        $this->id = $id;
        $publicKeys = [];
        $privateKey = null;
        $secrets = [];
        if (!is_array($keys)) {
            $keys = [$keys];
        }
        foreach ($keys as $key) {
            try {
                $pkiKey = PublicKeyLoader::load($key);
            } catch (\Exception $e) {
            }

            if (empty($pkiKey)) {
			    if (0 != strpos($key, 'BEGIN')) {
                    throw new KeyException('Input looks like PEM but key not understood using phpseclib3', 1);
                } elseif (!empty($publicKeys) || !empty($privateKsy)) {
                    throw new KeyException('PKI Key(s) and Secret provided, only one type of key supported', 1);
                } else {
                    $secrets[hash('sha256', $key)] = $key;
                }
            } else {
                $type = explode('\\', get_class($pkiKey))[3];
			    switch ($type) {
                  case 'PrivateKey':
                    if (!empty($privateKey)) {
                        throw new KeyException('Multiple Private Keys Provided, only one signing key supported', 1);
                    }
                    if (!empty($secrets)) {
                        throw new KeyException('Private Key and Secret provided, only one type of signing key supported', 1);
                    }
                    $fingerPrint = hash('sha256', $pkiKey->getPublicKey()->toString('PKCS8'));
                    $privateKey = $pkiKey;
                    $publicKeys[$fingerPrint] = $pkiKey->getPublicKey();
                    break;
                  case 'PublicKey':
                    $fingerPrint = hash('sha256', $pkiKey->toString('PKCS8'));
                    if (!empty($secrets)) {
                        throw new KeyException('Public Key and Secret provided, only one type of verifying key supported', 1);
                    } elseif (!empty($privateKey) && !array_key_exists($fingerPrint, $publicKeys)) {
                        throw new KeyException("Public Key and Private Key don't seem to be related", 1);
                    } else {
                        $publicKeys[$fingerPrint] = $pkiKey;
                    }
                    break;

                  default:
                    throw new KeyException('Something went terribly wrong, not a secret and not PKI - should never happen', 1);
                    break;
                }
            }
        }
        if (!empty($publicKeys)) {
            $this->privateKey = $privateKey;
            $this->publicKeys = $publicKeys;
            $this->algorithm = explode('\\', get_class($pkiKey))[2];
            if ('EC' == $this->algorithm) {
                $this->curve = current($publicKeys)->getCurve();
            }
        } else {
            $this->algorithm = 'HMAC';
            $this->secrets = $secrets;
        }
    }

    /**
     * Retrieves private key resource from a input string.
     *
     * @param string $key PEM-format Private Key
     *
     * @return resource|false
     */
    private static function getPrivateKey($key)
    {
        if (Key::hasPrivateKey($key)) {
            return PublicKeyLoader::load($key)->toString('PKCS8');
        } else {
            return null;
        }
    }

    /**
     * Retrieves public key resource from a input string.
     *
     * @param string $object PEM-format Public Key or file path to same
     *
     * @return resource|false
     */
    private static function getPublicKey($candidate)
    {
        try {
            $key = PublicKeyLoader::load($candidate);
        } catch (\Exception $e) {
            return null;
        }
        if (!empty($key)) {
            return $key;
        } else {
            return null;
        }
    }

    public static function fromX509Certificate($certificate)
    {
        return Key::getPublicKey($certificate);
    }

    /**
     * Signing HTTP Messages 'keyId' field.
     *
     * @return string
     *
     * @throws KeyException
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Retrieve Verifying Key - Public Key for Asymmetric/PKI, or shared secret for HMAC.
     *
     * @return string Shared Secret or PEM-format Public Key
     *
     * @throws KeyException
     */
    public function getVerifyingKey($format = 'PKCS8')
    {
        switch ($this->getClass()) {
        case 'asymmetric':
            if (1 != sizeof($this->publicKeys)) {
                throw new KeyException('More than one Verifying Key. Use getVerifyingKeys() instead', 1);
            // TODO: Implement getVerifyingKeys and multiple key verification
                // https://github.com/liamdennehy/http-signatures-php/issues/20
            } else {
                return str_replace("\r\n", "\n", current($this->publicKeys)->toString($format));
            }
            break;
        case 'secret':
          if (1 != sizeof($this->secrets)) {
              throw new KeyException('More than one Secret Key. Use getVerifyingKeys() instead', 1);
          } else {
              return current($this->secrets);
          }
          // no break
        default:
            throw new KeyException("Unknown key class $this->class");
        }
    }

    /**
     * Retrieve Signing Key - Private Key for Asymmetric/PKI, or shared secret for HMAC.
     *
     * @return string Shared Secret or PEM-format Private Key
     *
     * @throws KeyException
     */
    public function getSigningKey($format = 'PKCS8')
    {
        switch ($this->getClass()) {
        case 'asymmetric':
            if (!empty($this->privateKey)) {
                return str_replace("\r\n", "\n", $this->privateKey->toString($format));
            } else {
                return null;
            }
            break;
        case 'secret':
            if (sizeof($this->secrets) > 1) {
                throw new KeyException('Multiple Secrets in Key, use only one as input for signing');
            } else {
                return current($this->secrets);
            }
            // no break
        default:
            throw new KeyException("Unknown key class $this->class");
        }
    }

    /**
     * @return string 'secret' for HMAC or 'asymmetric' for RSA/EC
     */
    public function getClass()
    {
        if (!empty($this->publicKeys)) {
            return 'asymmetric';
        } else {
            return 'secret';
        }
    }

    public function getType()
    {
        switch ($this->getClass()) {
          case 'secret':
            return 'hmac';
            break;

          case 'asymmetric':
            return strtolower($this->algorithm);
            break;

          default:
            throw new KeyException("Unknown key class '{$this->class}' fetching algorithm", 1);
            break;
        }
    }

    public function getCurve()
    {
        return $this->curve;
    }

    /**
     * Test if $object is, points to or contains, X.509 PEM-format certificate.
     *
     * @param string|array $object PEM Format X.509 Certificate or file path to one
     *
     * @return bool
     */
    public static function isX509Certificate($candidate)
    {
        try {
            $x509 = new X509();
            $x509->loadX509($candidate);
            $key = $x509->getPublicKey();
            if ($key) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function isPublicKey($object)
    {
        return
        Key::hasPublicKey($object) &&
        !Key::hasPrivateKey($object) &&
        !Key::isX509Certificate($object)
      ;
    }

    public static function isPrivateKey($object)
    {
        return
        Key::hasPrivateKey($object) &&
        !Key::isPublicKey($object)
      ;
    }

    public static function hasPKIKey($item)
    {
        return
        Key::hasPublicKey($item) ||
        Key::hasPrivateKey($item)
      ;
    }

    public static function hasPublicKey($candidate)
    {
        if (empty($candidate)) {
            return false;
        } elseif (is_string($candidate)) {
            try {
                $key = PublicKeyLoader::load($candidate);
                if (empty($key)) {
                    return false;
                }
                if ('PrivateKey' === explode('\\', get_class($key))[3]) {
                    $key = $key->getPublicKey();
                    if (empty($key)) {
                        return false;
                    }
                }

                return 'PublicKey' === explode('\\', get_class($key))[3];
            } catch (\Exception $e) {
                return false;
            }
        }
    }

    /**
     * Test if $object is, points to or contains, PEM-format Private Key.
     *
     * @param string|array $object PEM-format Private Key or file path to one
     *
     * @return bool
     */
    public static function hasPrivateKey($candidate)
    {
        if (empty($candidate)) {
            return false;
        } elseif (is_string($candidate)) {
            try {
                $key = PublicKeyLoader::load($candidate);
                if (empty($key)) {
                    return false;
                }

                return 'PrivateKey' === explode('\\', get_class($key))[3];
            } catch (\Exception $e) {
                return false;
            }
        }
    }

    public static function isPKIKey($item)
    {
        return
        Key::isPrivateKey($item) ||
        Key::isPublicKey($item)
      ;
    }
}
