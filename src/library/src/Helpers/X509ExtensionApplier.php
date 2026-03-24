<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Helpers;

use KDuma\PhpCA\Record\Extension\BaseExtension;
use KDuma\PhpCA\Record\Extension\Extensions\AuthorityInfoAccessExtension;
use KDuma\PhpCA\Record\Extension\Extensions\BasicConstraintsExtension;
use KDuma\PhpCA\Record\Extension\Extensions\CertificatePoliciesExtension;
use KDuma\PhpCA\Record\Extension\Extensions\CrlDistributionPointsExtension;
use KDuma\PhpCA\Record\Extension\Extensions\ExtKeyUsageExtension;
use KDuma\PhpCA\Record\Extension\Extensions\KeyUsageExtension;
use KDuma\PhpCA\Record\Extension\Extensions\NetscapeCommentExtension;
use KDuma\PhpCA\Record\Extension\Extensions\PrivateKeyUsagePeriodExtension;
use KDuma\PhpCA\Record\Extension\Extensions\SubjectAltNameExtension;
use phpseclib3\Crypt\Common\PublicKey;
use phpseclib3\File\X509;

class X509ExtensionApplier
{
    /**
     * Apply BaseExtension[] to a phpseclib X509 object before signing.
     * SKI and AKI are NOT applied here — they are auto-computed by phpseclib.
     *
     * @param  BaseExtension[]  $extensions
     */
    public static function apply(X509 $x509, array $extensions): void
    {
        foreach ($extensions as $extension) {
            match (true) {
                $extension instanceof BasicConstraintsExtension => self::applyBasicConstraints($x509, $extension),
                $extension instanceof KeyUsageExtension => self::applyKeyUsage($x509, $extension),
                $extension instanceof ExtKeyUsageExtension => self::applyExtKeyUsage($x509, $extension),
                $extension instanceof SubjectAltNameExtension => self::applySubjectAltName($x509, $extension),
                $extension instanceof CrlDistributionPointsExtension => self::applyCrlDistributionPoints($x509, $extension),
                $extension instanceof AuthorityInfoAccessExtension => self::applyAuthorityInfoAccess($x509, $extension),
                $extension instanceof NetscapeCommentExtension => self::applyNetscapeComment($x509, $extension),
                $extension instanceof CertificatePoliciesExtension => self::applyCertificatePolicies($x509, $extension),
                $extension instanceof PrivateKeyUsagePeriodExtension => self::applyPrivateKeyUsagePeriod($x509, $extension),
                default => null, // SKI, AKI, and unknown extensions are skipped
            };
        }
    }

    private static function applyBasicConstraints(X509 $x509, BasicConstraintsExtension $ext): void
    {
        $value = ['cA' => $ext->ca];
        if ($ext->pathLenConstraint !== null) {
            $value['pathLenConstraint'] = $ext->pathLenConstraint;
        }

        $x509->setExtensionValue('id-ce-basicConstraints', $value, $ext->isCritical());
    }

    private static function applyKeyUsage(X509 $x509, KeyUsageExtension $ext): void
    {
        $usages = [];
        if ($ext->digitalSignature) {
            $usages[] = 'digitalSignature';
        }
        if ($ext->nonRepudiation) {
            $usages[] = 'nonRepudiation';
        }
        if ($ext->keyEncipherment) {
            $usages[] = 'keyEncipherment';
        }
        if ($ext->dataEncipherment) {
            $usages[] = 'dataEncipherment';
        }
        if ($ext->keyAgreement) {
            $usages[] = 'keyAgreement';
        }
        if ($ext->keyCertSign) {
            $usages[] = 'keyCertSign';
        }
        if ($ext->cRLSign) {
            $usages[] = 'cRLSign';
        }
        if ($ext->encipherOnly) {
            $usages[] = 'encipherOnly';
        }
        if ($ext->decipherOnly) {
            $usages[] = 'decipherOnly';
        }

        if (! empty($usages)) {
            $x509->setExtensionValue('id-ce-keyUsage', $usages, $ext->isCritical());
        }
    }

    private static function applyExtKeyUsage(X509 $x509, ExtKeyUsageExtension $ext): void
    {
        if (! empty($ext->usages)) {
            $mapped = array_map(fn (string $u) => match ($u) {
                'serverAuth' => 'id-kp-serverAuth',
                'clientAuth' => 'id-kp-clientAuth',
                'codeSigning' => 'id-kp-codeSigning',
                'emailProtection' => 'id-kp-emailProtection',
                'timeStamping' => 'id-kp-timeStamping',
                'OCSPSigning' => 'id-kp-OCSPSigning',
                default => $u,
            }, $ext->usages);

            $x509->setExtensionValue('id-ce-extKeyUsage', $mapped, $ext->isCritical());
        }
    }

    private static function applySubjectAltName(X509 $x509, SubjectAltNameExtension $ext): void
    {
        $names = [];

        foreach ($ext->dnsNames as $dns) {
            $names[] = ['dNSName' => $dns];
        }
        foreach ($ext->ipAddresses as $ip) {
            $names[] = ['iPAddress' => $ip];
        }
        foreach ($ext->emails as $email) {
            $names[] = ['rfc822Name' => $email];
        }
        foreach ($ext->uris as $uri) {
            $names[] = ['uniformResourceIdentifier' => $uri];
        }

        if (! empty($names)) {
            $x509->setExtensionValue('id-ce-subjectAltName', $names, $ext->isCritical());
        }
    }

    private static function applyCrlDistributionPoints(X509 $x509, CrlDistributionPointsExtension $ext): void
    {
        if (! empty($ext->uris)) {
            $points = [
                [
                    'distributionPoint' => [
                        'fullName' => array_map(
                            fn (string $uri) => ['uniformResourceIdentifier' => $uri],
                            $ext->uris,
                        ),
                    ],
                ],
            ];

            $x509->setExtensionValue('id-ce-cRLDistributionPoints', $points, $ext->isCritical());
        }
    }

    private static function applyAuthorityInfoAccess(X509 $x509, AuthorityInfoAccessExtension $ext): void
    {
        $accessDescriptions = [];

        foreach ($ext->ocspUris as $uri) {
            $accessDescriptions[] = [
                'accessMethod' => 'id-ad-ocsp',
                'accessLocation' => ['uniformResourceIdentifier' => $uri],
            ];
        }

        foreach ($ext->caIssuersUris as $uri) {
            $accessDescriptions[] = [
                'accessMethod' => 'id-ad-caIssuers',
                'accessLocation' => ['uniformResourceIdentifier' => $uri],
            ];
        }

        if (! empty($accessDescriptions)) {
            $x509->setExtensionValue('id-pe-authorityInfoAccess', $accessDescriptions, $ext->isCritical());
        }
    }

    private static function applyNetscapeComment(X509 $x509, NetscapeCommentExtension $ext): void
    {
        $x509->setExtensionValue('netscape-comment', $ext->comment, $ext->isCritical());
    }

    private static function applyCertificatePolicies(X509 $x509, CertificatePoliciesExtension $ext): void
    {
        if (empty($ext->policies)) {
            return;
        }

        $phpseclibPolicies = [];

        foreach ($ext->policies as $policy) {
            $policyInfo = ['policyIdentifier' => $policy['oid']];
            $qualifiers = [];

            if (isset($policy['cps'])) {
                $qualifiers[] = [
                    'policyQualifierId' => 'id-qt-cps',
                    'qualifier' => $policy['cps'],
                ];
            }

            $hasNotice = isset($policy['notice']);
            $hasNoticeRef = isset($policy['noticeRef']);

            if ($hasNotice || $hasNoticeRef) {
                $userNotice = [];

                if ($hasNotice) {
                    $userNotice['explicitText'] = ['utf8String' => $policy['notice']];
                }

                if ($hasNoticeRef) {
                    $userNotice['noticeRef'] = [
                        'organization' => ['utf8String' => $policy['noticeRef']['organization']],
                        'noticeNumbers' => $policy['noticeRef']['noticeNumbers'],
                    ];
                }

                $qualifiers[] = [
                    'policyQualifierId' => 'id-qt-unotice',
                    'qualifier' => $userNotice,
                ];
            }

            if (! empty($qualifiers)) {
                $policyInfo['policyQualifiers'] = $qualifiers;
            }

            $phpseclibPolicies[] = $policyInfo;
        }

        $x509->setExtensionValue('id-ce-certificatePolicies', $phpseclibPolicies, $ext->isCritical());
    }

    private static function applyPrivateKeyUsagePeriod(X509 $x509, PrivateKeyUsagePeriodExtension $ext): void
    {
        $value = [];
        if ($ext->notBefore !== null) {
            $value['notBefore'] = $ext->notBefore->format('D, d M Y H:i:s O');
        }
        if ($ext->notAfter !== null) {
            $value['notAfter'] = $ext->notAfter->format('D, d M Y H:i:s O');
        }

        if (! empty($value)) {
            $x509->setExtensionValue('id-ce-privateKeyUsagePeriod', $value, $ext->isCritical());
        }
    }

    /**
     * Compute and set Subject Key Identifier on the subject X509.
     * Uses SHA-1 of the public key BIT STRING per RFC 5280 §4.2.1.2.
     */
    public static function setSubjectKeyIdentifier(X509 $subject, PublicKey $publicKey): void
    {
        // Use PKCS8/SubjectPublicKeyInfo format (works for all key types),
        // then extract the BIT STRING (public key data) for SHA-1 per RFC 5280 §4.2.1.2
        $der = $publicKey->toString('PKCS8');

        // Parse SubjectPublicKeyInfo to extract the raw public key BIT STRING
        // The structure is: SEQUENCE { algorithm, BIT STRING }
        // We hash the BIT STRING content (the actual key bits)
        $pem = $der;
        $pem = preg_replace('/-----[^-]+-----/', '', $pem);
        $pem = base64_decode(preg_replace('/\s+/', '', $pem));

        // Use the full SubjectPublicKeyInfo DER as input — this matches
        // what phpseclib's makeCA() does internally
        $ski = sha1($pem, true);

        $subject->setExtensionValue('id-ce-subjectKeyIdentifier', $ski);
    }
}
