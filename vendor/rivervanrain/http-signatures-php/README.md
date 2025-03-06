# Signing HTTP Messages PSR-7 Library

[![Build Status](https://travis-ci.org/liamdennehy/http-signatures-php.svg?branch=master)](https://travis-ci.org/liamdennehy/http-signatures-php)
[![Documentation Status](https://readthedocs.org/projects/http-signatures-php/badge/?version=latest)](https://http-signatures-php.readthedocs.io/en/latest/?badge=latest)

PHP implementation of [Signing HTTP Messages][draft10] draft specification;
allowing cryptographic signing and verifying of [PSR-7 messages][psr7].

## Features

- Sign HTTP Messages according to
  [Signing HTTP Message draft IETF RFC version 10][draft10]
- Sign & verify messages using HMACs
- Sign & verify messages with Asymmetric Keys:
  - RSA, DSA, EC
- Add a ``Digest`` header, or automatically add the header while signing in a
  single operation
- Verify a ``Digest`` header while verifying the signature

Complete documentation for this library can be found at
[Read The Docs](https://http-signatures-php.readthedocs.io/en/latest/)

**WARNING:** Version 11 of this library incorporates
[phpseclib](http://phpseclib.sourceforge.net/)'s ongoing work on their
[version 3.0 implementation](https://github.com/phpseclib/phpseclib/tree/3.0).
If there are any problems please log an issue, but as the library has not been
stabilised or completely reviewed you are advised to proceed with caution,
or remain at v10 of this library until phpseclib 3.0 is complete and the
MAJOR version of this library is bumped.

## Simple Usage

Add [liamdennehy/http-signatures-php][package] to your [``composer.json``][composer].

* A message is assumed to be a PSR-7 compatible Request or Response.
* A ``Context`` object is used to configure the signature parameters, and prepare
  the verifier functionality.
* The ``signWithDigest`` function witll add a ``Digest`` header and digitally
  sign the message in a new ``Signature`` header.

Signing a PSR-7 request ``$message`` before sending:

```php
  use HttpSignatures\Context;

  $context = new HttpSignatures\Context([
    'keys' => ['mykey' => file_get_contents('/path/to/privatekeyfile')],
    'algorithm' => 'rsa-sha256',
    'headers' => ['(request-target)', 'Date'],
  ]);

  $context->signer()->signWithDigest($message);
```

Complete documentation for this library for other ose cases can be found at
[Read The Docs](https://http-signatures-php.readthedocs.io/en/latest/)

## Contributing

Pull Requests are welcome, as are
[issue reports][github-issues] if you encounter any problems.

**Note**: Due to composer dependencies for the reference implementation
``composer install`` prior to local development is only posible on PHP 7.1,
or by manually removing the incompatible dependencies using the command
(wrapped for readability):

```sh
  composer remove --dev \
  nyholm/psr7 nyholm/psr7-server riswallsmith/buzz \
  endframework/zend-httphandlerrunner
```
[draft10]: http://tools.ietf.org/html/draft-cavage-http-signatures-10
[Symfony\Component\HttpFoundation\Request]: https://github.com/symfony/HttpFoundation/blob/master/Request.php
[composer]: https://getcomposer.org/
[package]: https://packagist.org/packages/liamdennehy/http-signatures-php
[github-issues]: https://github.com/liamdennehy/http-signatures-php/issues
[psr7]: http://www.php-fig.org/psr/psr-7/

## License

HTTP Signatures PHP library is licensed under
[The MIT License (MIT)](https://opensource.org/licenses/MIT).

Documentation of the library is licensed under
[Creative Commons Attribution-ShareAlike 4.0 International (CC BY-SA 4.0)](https://creativecommons.org/licenses/by-sa/4.0/)

Details are in the [LICENSE file](./LICENSE.md)
