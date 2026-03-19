<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record;

use KDuma\PhpCA\Record\CertificateSubject\CertificateSubject;
use KDuma\PhpCA\Record\Converter\CertificateSubjectConverter;
use KDuma\PhpCA\Record\Converter\ExtensionsConverter;
use KDuma\SimpleDAL\Typed\Contracts\Attribute\Field;
use KDuma\SimpleDAL\Typed\Contracts\TypedRecord;

class CsrRecord extends TypedRecord
{
    #[Field(converter: CertificateSubjectConverter::class)]
    public CertificateSubject $subject;

    #[Field]
    public string $keyId;

    #[Field]
    public ?string $certificateId;

    #[Field(converter: ExtensionsConverter::class)]
    public array $requestedExtensions;

    #[Field]
    public string $fingerprint;
}
