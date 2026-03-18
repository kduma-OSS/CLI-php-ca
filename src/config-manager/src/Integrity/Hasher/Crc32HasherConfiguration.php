<?php

declare(strict_types=1);

namespace KDuma\PhpCA\ConfigManager\Integrity\Hasher;

use KDuma\PhpCA\ConfigManager\Integrity\Hasher\Attributes\HasherConfiguration;
use KDuma\SimpleDAL\Integrity\Contracts\HashingAlgorithmInterface;
use KDuma\SimpleDAL\Integrity\Hash\Hasher\Crc32HashingAlgorithm;

#[HasherConfiguration('crc32')]
readonly class Crc32HasherConfiguration extends BaseHasherConfiguration
{
    public function createHasher(): HashingAlgorithmInterface
    {
        return new Crc32HashingAlgorithm();
    }

    public static function fromArray(array $data): static
    {
        return new static();
    }

    public function toArray(): array
    {
        return ['type' => static::getType()];
    }
}
