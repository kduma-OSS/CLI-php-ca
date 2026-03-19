<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Helpers;

use phpseclib3\Crypt\Common\PrivateKey;
use phpseclib3\Crypt\Common\PublicKey;
use phpseclib3\Crypt\RSA;

class KeyHelper
{
    /**
     * Prepare a private key for signing operations.
     * For RSA keys, defaults to PKCS1 v1.5 (sha256WithRSAEncryption)
     * instead of phpseclib's default RSASSA-PSS.
     */
    public static function prepareForSigning(PrivateKey $key): PrivateKey
    {
        if ($key instanceof RSA\PrivateKey) {
            return $key->withPadding(RSA::SIGNATURE_PKCS1);
        }

        return $key;
    }

    /**
     * Prepare a public key for embedding in a certificate.
     * For RSA keys, ensures rsaEncryption OID instead of rsassaPss.
     */
    public static function preparePublicKey(PublicKey $key): PublicKey
    {
        if ($key instanceof RSA\PublicKey) {
            return $key->withPadding(RSA::SIGNATURE_PKCS1 | RSA::ENCRYPTION_PKCS1);
        }

        return $key;
    }
}
