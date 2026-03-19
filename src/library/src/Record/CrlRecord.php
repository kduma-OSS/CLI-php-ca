<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record;

use DateTimeImmutable;
use KDuma\PhpCA\Record\CertificateSubject\CertificateSubject;
use KDuma\PhpCA\Record\Converter\CertificateSubjectConverter;
use KDuma\PhpCA\Record\Enum\SignatureAlgorithm;
use KDuma\SimpleDAL\Typed\Contracts\Attribute\Field;
use KDuma\SimpleDAL\Typed\Contracts\TypedRecord;
use KDuma\SimpleDAL\Typed\Converter\DateTimeConverter;

class CrlRecord extends TypedRecord
{
    #[Field]
    public string $signerKeyId;

    #[Field]
    public ?string $signerCertificateId;

    #[Field]
    public ?string $caCertificateId;

    #[Field(converter: CertificateSubjectConverter::class)]
    public CertificateSubject $issuer;

    #[Field(converter: DateTimeConverter::class)]
    public DateTimeImmutable $thisUpdate;

    #[Field(converter: DateTimeConverter::class)]
    public ?DateTimeImmutable $nextUpdate;

    #[Field]
    public int $crlNumber;

    #[Field]
    public SignatureAlgorithm $signatureAlgorithm;
}
