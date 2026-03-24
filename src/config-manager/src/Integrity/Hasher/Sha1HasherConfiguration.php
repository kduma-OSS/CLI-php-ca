<?php

declare(strict_types=1);

namespace KDuma\PhpCA\ConfigManager\Integrity\Hasher;

use KDuma\PhpCA\ConfigManager\Integrity\Hasher\Attributes\HasherConfiguration;
use KDuma\SimpleDAL\Integrity\Contracts\HashingAlgorithmInterface;
use KDuma\SimpleDAL\Integrity\Hash\Hasher\Sha1HashingAlgorithm;

#[HasherConfiguration('sha1')]
readonly class Sha1HasherConfiguration extends BaseHasherConfiguration
{
    public function createHasher(): HashingAlgorithmInterface
    {
        return new Sha1HashingAlgorithm;
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
