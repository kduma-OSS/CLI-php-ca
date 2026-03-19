<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record;

use DateInterval;
use KDuma\PhpCA\Record\Converter\DateIntervalConverter;
use KDuma\PhpCA\Record\Converter\ExtensionTemplatesConverter;
use KDuma\SimpleDAL\Typed\Contracts\Attribute\Field;
use KDuma\SimpleDAL\Typed\Contracts\TypedRecord;

class CertificateTemplateRecord extends TypedRecord
{
    #[Field]
    public string $displayName;

    #[Field]
    public ?string $parentId;

    #[Field(converter: DateIntervalConverter::class)]
    public ?DateInterval $validity;

    #[Field(converter: ExtensionTemplatesConverter::class)]
    public array $extensions;
}
