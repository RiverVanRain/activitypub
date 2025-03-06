# CHANGELOG

## 11.0.0-beta1

- Move phpseclib from git ref to "stable" liamdennehy/phpseclib.
  - Temporary dependency until phpseclib 3.0 stabilises, avoids having to
    use direct git commit refs and library appears to have "stable" only deps.
  - phpseclib 3.0 is still in development, but API is unlikely to drastically
    change, and once rleeased this dependency will revert to the official
    package (with any required fixes).

## 11.0.0-alpha3

- Remove all openssl depedencencies
  - Functionality becomes tied to whatever version of openssl libraries
    are compiled into PHP, leading to difficulty predicting which ciphers
    are supported
  - OpenSSL functions are difficult to use with very different bahaviour (e.g.
    little consistency on exceptions vs silent failure, some functions returning
    vaues while other place return values in parameters)
- phpseclib for all crypto functions
  - phpselcib 3.0 not yet stable so pull directly from master
- Key class behaviour altered (interface remains same)
  - Asymmetric: Only permit one private key, and only return one signing key.
    Exception at creation for early failure.
