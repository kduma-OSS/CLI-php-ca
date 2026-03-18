<?php

declare(strict_types=1);

namespace KDuma\PhpCA\ConfigManager\Integrity\Signer;

use KDuma\SimpleDAL\Integrity\Contracts\SigningAlgorithmInterface;

abstract readonly class BaseSignerConfiguration
{
    abstract public function createSigner(): SigningAlgorithmInterface;

    abstract public static function fromArray(array $data, string $basePath): static;

    abstract public function toArray(): array;

    public static function getType(): string
    {
        return SignerConfigurationFactory::getTypeForClass(static::class);
    }
}
