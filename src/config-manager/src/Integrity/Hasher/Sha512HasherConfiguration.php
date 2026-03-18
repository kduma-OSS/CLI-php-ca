<?php

declare(strict_types=1);

namespace KDuma\PhpCA\ConfigManager\Integrity\Hasher;

use KDuma\PhpCA\ConfigManager\Integrity\Hasher\Attributes\HasherConfiguration;
use KDuma\SimpleDAL\Integrity\Contracts\HashingAlgorithmInterface;
use KDuma\SimpleDAL\Integrity\Hash\Hasher\Sha512HashingAlgorithm;

#[HasherConfiguration('sha512')]
readonly class Sha512HasherConfiguration extends BaseHasherConfiguration
{
    public function createHasher(): HashingAlgorithmInterface
    {
        return new Sha512HashingAlgorithm();
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
