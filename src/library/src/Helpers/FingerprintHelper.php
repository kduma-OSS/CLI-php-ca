<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Helpers;

use phpseclib3\Crypt\Common\PublicKey;
use phpseclib3\File\X509;

class FingerprintHelper
{
    public static function compute(PublicKey $publicKey): string
    {
        try {
            return self::fromPhpSecLib($publicKey->getFingerprint('sha256'));
        } catch (\Exception) {
            $pem = $publicKey->toString('PKCS8');

            return hash('sha256', $pem);
        }
    }

    public static function fromPhpSecLib(string $base64Fingerprint): string
    {
        return bin2hex(base64_decode($base64Fingerprint));
    }

    public static function toPhpSecLib(string $hexFingerprint): string
    {
        return base64_encode(hex2bin($hexFingerprint));
    }

    public static function computeCertificateFingerprint(string $pem): string
    {
        $x509 = new X509;
        $x509->loadX509($pem);
        $der = $x509->saveX509($x509->getCurrentCert(), X509::FORMAT_DER);

        return hash('sha1', $der);
    }
}
