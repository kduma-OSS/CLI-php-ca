<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record;

use KDuma\PhpCA\Record\CertificateSubject\CertificateSubject;
use KDuma\PhpCA\Record\Converter\CertificateSubjectConverter;
use KDuma\PhpCA\Record\Converter\CertificateValidityConverter;
use KDuma\PhpCA\Record\Converter\ExtensionsConverter;
use KDuma\PhpCA\Record\Enum\SignatureAlgorithm;
use KDuma\SimpleDAL\Typed\Contracts\Attribute\Field;
use KDuma\SimpleDAL\Typed\Contracts\TypedRecord;

class CACertificateRecord extends TypedRecord
{
    #[Field]
    public int $version;

    #[Field]
    public string $serialNumber;

    #[Field]
    public SignatureAlgorithm $signatureAlgorithm;

    #[Field(converter: CertificateSubjectConverter::class)]
    public CertificateSubject $issuer;

    #[Field(converter: CertificateSubjectConverter::class)]
    public CertificateSubject $subject;

    #[Field(converter: CertificateValidityConverter::class)]
    public CertificateValidity $validity;

    #[Field]
    public string $subjectKeyIdentifier;

    #[Field]
    public string $authorityKeyIdentifier;

    #[Field(converter: ExtensionsConverter::class)]
    public array $extensions;

    #[Field]
    public string $keyId;

    #[Field]
    public string $fingerprint;

    #[Field]
    public bool $isSelfSigned;
}
