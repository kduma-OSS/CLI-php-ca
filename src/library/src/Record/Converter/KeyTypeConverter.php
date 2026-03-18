<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\Converter;

use KDuma\PhpCA\Record\KeyType\BaseKeyType;
use KDuma\SimpleDAL\Typed\Contracts\Converter\FieldConverterInterface;

class KeyTypeConverter implements FieldConverterInterface
{
    public function fromStorage(mixed $value): BaseKeyType
    {
        assert(is_array($value));

        return BaseKeyType::fromArray($value);
    }

    public function toStorage(mixed $value): array
    {
        assert($value instanceof BaseKeyType);

        return $value->toArray();
    }
}
