<?php

declare(strict_types=1);

namespace KDuma\PhpCA\ConfigManager\Integrity\Hasher;

use KDuma\PhpCA\ConfigManager\Integrity\Hasher\Attributes\HasherConfiguration;
use KDuma\SimpleDAL\Integrity\Contracts\HashingAlgorithmInterface;
use KDuma\SimpleDAL\Integrity\Sodium\Blake2bHashingAlgorithm;

#[HasherConfiguration('blake2b')]
readonly class Blake2bHasherConfiguration extends BaseHasherConfiguration
{
    public function createHasher(): HashingAlgorithmInterface
    {
        return new Blake2bHashingAlgorithm();
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
