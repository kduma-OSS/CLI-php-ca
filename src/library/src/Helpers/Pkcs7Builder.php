<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Helpers;

use phpseclib3\File\ASN1;
use phpseclib3\File\X509;

class Pkcs7Builder
{
    private const OID_SIGNED_DATA = '1.2.840.113549.1.7.2';

    private const OID_DATA = '1.2.840.113549.1.7.1';

    /**
     * Build a PKCS#7 certificate bundle (SignedData with no signers) in DER format.
     *
     * @param  string[]  $pemCertificates  Array of PEM-encoded certificates (may contain multi-cert PEM strings)
     * @return string Raw DER bytes
     */
    public static function buildCertificateBundle(array $pemCertificates): string
    {
        $derCerts = [];
        foreach ($pemCertificates as $pem) {
            foreach (self::splitPem($pem) as $singlePem) {
                $x509 = new X509;
                $x509->loadX509($singlePem);
                $derCerts[] = $x509->saveX509($x509->getCurrentCert(), X509::FORMAT_DER);
            }
        }

        return self::buildSignedData($derCerts);
    }

    /**
     * Build a PKCS#7 certificate bundle in PEM format.
     *
     * @param  string[]  $pemCertificates  Array of PEM-encoded certificates
     * @return string PEM-encoded PKCS#7
     */
    public static function buildCertificateBundlePem(array $pemCertificates): string
    {
        $der = self::buildCertificateBundle($pemCertificates);

        return "-----BEGIN PKCS7-----\n"
            .chunk_split(base64_encode($der), 64, "\n")
            ."-----END PKCS7-----\n";
    }

    /**
     * Split a PEM string that may contain multiple certificates into individual PEM strings.
     *
     * @return string[]
     */
    private static function splitPem(string $pem): array
    {
        preg_match_all(
            '/-----BEGIN CERTIFICATE-----.*?-----END CERTIFICATE-----/s',
            $pem,
            $matches,
        );

        return $matches[0] ?: [];
    }

    /**
     * Build the PKCS#7 SignedData ASN.1 DER structure.
     *
     * @param  string[]  $derCertificates  Array of DER-encoded certificates
     */
    private static function buildSignedData(array $derCertificates): string
    {
        $version = self::asn1Integer(1);
        $digestAlgorithms = self::asn1Set('');
        $contentInfo = self::asn1Sequence(self::asn1Oid(self::OID_DATA));
        $certificates = self::asn1ConstructedContextTag(0, implode('', $derCertificates));
        $signerInfos = self::asn1Set('');

        $signedData = self::asn1Sequence(
            $version.$digestAlgorithms.$contentInfo.$certificates.$signerInfos,
        );

        return self::asn1Sequence(
            self::asn1Oid(self::OID_SIGNED_DATA)
            .self::asn1ConstructedContextTag(0, $signedData),
        );
    }

    private static function asn1Sequence(string $content): string
    {
        return "\x30".ASN1::encodeLength(strlen($content)).$content;
    }

    private static function asn1Set(string $content): string
    {
        return "\x31".ASN1::encodeLength(strlen($content)).$content;
    }

    private static function asn1Integer(int $value): string
    {
        $content = chr($value);

        return "\x02".ASN1::encodeLength(strlen($content)).$content;
    }

    private static function asn1Oid(string $oid): string
    {
        $content = ASN1::encodeOID($oid);

        return "\x06".ASN1::encodeLength(strlen($content)).$content;
    }

    private static function asn1ConstructedContextTag(int $tag, string $content): string
    {
        return chr(0xA0 | $tag).ASN1::encodeLength(strlen($content)).$content;
    }
}
