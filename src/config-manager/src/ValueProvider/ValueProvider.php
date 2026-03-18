<?php

declare(strict_types=1);

namespace KDuma\PhpCA\ConfigManager\ValueProvider;

abstract readonly class ValueProvider
{
    abstract public function resolve(): string;

    abstract public static function fromArray(array $data, string $basePath): static;

    abstract public function toArray(): string|array;

    public static function getType(): string
    {
        return ValueProviderFactory::getTypeForClass(static::class);
    }
}
