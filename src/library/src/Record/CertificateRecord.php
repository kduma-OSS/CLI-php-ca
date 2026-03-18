<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record;

use DateTimeImmutable;
use KDuma\SimpleDAL\Typed\Contracts\Attribute\Field;
use KDuma\SimpleDAL\Typed\Contracts\TypedRecord;
use KDuma\SimpleDAL\Typed\Converter\DateTimeConverter;

class CertificateRecord extends TypedRecord
{
    #[Field]
    public string $keyId;

    #[Field]
    public string $serialNumber;

    #[Field]
    public int $sequence;

    #[Field(converter: DateTimeConverter::class)]
    public ?DateTimeImmutable $revokedAt;
}
