<?php

namespace HttpSignatures;

abstract class Algorithm
{
    /**
     * @param string $name
     *
     * @return HmacAlgorithm
     *
     * @throws Exception
     */
    public static function create($name)
    {
        switch ($name) {
        case 'hmac-sha1':
            return new HmacAlgorithm('sha1');
            break;
        case 'hmac-sha256':
            return new HmacAlgorithm('sha256');
            break;
        case 'rsa-sha1':
            return new RsaAlgorithm('sha1');
            break;
        case 'rsa-sha256':
            return new RsaAlgorithm('sha256');
            break;
        case 'dsa-sha1':
            return new DsaAlgorithm('sha1');
            break;
        case 'dsa-sha256':
            return new DsaAlgorithm('sha256');
            break;
        case 'ec-sha1':
            return new EcAlgorithm('sha1');
            break;
        case 'ec-sha256':
            return new EcAlgorithm('sha256');
            break;
        default:
            throw new AlgorithmException("No algorithm named '$name'");
            break;
        }
    }
}
