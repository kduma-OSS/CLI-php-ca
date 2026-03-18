<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record;

use KDuma\PhpCA\Record\Converter\KeyTypeConverter;
use KDuma\PhpCA\Record\KeyType\BaseKeyType;
use KDuma\SimpleDAL\Typed\Contracts\Attribute\Field;
use KDuma\SimpleDAL\Typed\Contracts\TypedRecord;

class KeyRecord extends TypedRecord
{
    #[Field(converter: KeyTypeConverter::class)]
    public BaseKeyType $type;

    #[Field]
    public string $fingerprint;

    #[Field]
    public bool $hasPrivateKey;
}
