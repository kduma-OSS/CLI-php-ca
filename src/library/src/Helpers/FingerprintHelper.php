<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Helpers;

use phpseclib3\Crypt\Common\PublicKey;

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
}
