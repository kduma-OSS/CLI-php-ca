<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record;

use DateTimeImmutable;
use KDuma\PhpCA\Record\Enum\RevocationReason;
use KDuma\SimpleDAL\Typed\Contracts\Attribute\Field;
use KDuma\SimpleDAL\Typed\Contracts\TypedRecord;
use KDuma\SimpleDAL\Typed\Converter\DateTimeConverter;

class RevocationRecord extends TypedRecord
{
    #[Field]
    public string $certificateId;

    #[Field]
    public string $serialNumber;

    #[Field(converter: DateTimeConverter::class)]
    public DateTimeImmutable $revokedAt;

    #[Field]
    public RevocationReason $reason;

    #[Field]
    public ?string $caCertificateId;
}
