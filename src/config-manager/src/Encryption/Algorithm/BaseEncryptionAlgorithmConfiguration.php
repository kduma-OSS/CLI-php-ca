<?php

declare(strict_types=1);

namespace KDuma\PhpCA\ConfigManager\Encryption\Algorithm;

use KDuma\SimpleDAL\Encryption\Contracts\EncryptionAlgorithmInterface;

abstract readonly class BaseEncryptionAlgorithmConfiguration
{
    public function __construct(
        public string $id,
    ) {}

    abstract public function createAlgorithm(): EncryptionAlgorithmInterface;

    abstract public static function fromArray(array $data, string $basePath): static;

    abstract public function toArray(): array;

    public static function getType(): string
    {
        return EncryptionAlgorithmConfigurationFactory::getTypeForClass(static::class);
    }
}
