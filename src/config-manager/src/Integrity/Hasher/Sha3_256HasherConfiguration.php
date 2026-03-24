<?php

declare(strict_types=1);

namespace KDuma\PhpCA\ConfigManager\Integrity\Hasher;

use KDuma\PhpCA\ConfigManager\Integrity\Hasher\Attributes\HasherConfiguration;
use KDuma\SimpleDAL\Integrity\Contracts\HashingAlgorithmInterface;
use KDuma\SimpleDAL\Integrity\Hash\Hasher\Sha3_256HashingAlgorithm;

#[HasherConfiguration('sha3-256')]
readonly class Sha3_256HasherConfiguration extends BaseHasherConfiguration
{
    public function createHasher(): HashingAlgorithmInterface
    {
        return new Sha3_256HashingAlgorithm;
    }

    public static function fromArray(array $data): static
    {
        return new static;
    }

    public function toArray(): array
    {
        return ['type' => static::getType()];
    }
}
