<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\Enum;

enum SignatureAlgorithm: string
{
    case Sha256WithRSA = 'sha256WithRSAEncryption';
    case Sha384WithRSA = 'sha384WithRSAEncryption';
    case Sha512WithRSA = 'sha512WithRSAEncryption';
    case RsaPss = 'RSASSA-PSS';
    case Sha256WithECDSA = 'ecdsa-with-SHA256';
    case Sha384WithECDSA = 'ecdsa-with-SHA384';
    case Sha512WithECDSA = 'ecdsa-with-SHA512';
    case Ed25519 = 'Ed25519';
    case Ed448 = 'Ed448';

    /**
     * Create from an ASN.1 algorithm identifier string (e.g. "id-Ed25519").
     */
    public static function fromAsn1(string $algorithm): self
    {
        // phpseclib uses "id-Ed25519"/"id-Ed448" in ASN.1 structures
        $normalized = preg_replace('/^id-/', '', $algorithm);

        return self::from($normalized);
    }
}
