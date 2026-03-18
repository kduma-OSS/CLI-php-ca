<?php

declare(strict_types=1);

namespace KDuma\PhpCA\ConfigManager\Integrity\Hasher;

use KDuma\SimpleDAL\Integrity\Contracts\HashingAlgorithmInterface;

abstract readonly class BaseHasherConfiguration
{
    abstract public function createHasher(): HashingAlgorithmInterface;

    abstract public static function fromArray(array $data): static;

    abstract public function toArray(): array;

    public static function getType(): string
    {
        return HasherConfigurationFactory::getTypeForClass(static::class);
    }
}
