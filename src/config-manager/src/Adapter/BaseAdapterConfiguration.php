<?php

declare(strict_types=1);

namespace KDuma\PhpCA\ConfigManager\Adapter;

use KDuma\SimpleDAL\Adapter\Contracts\StorageAdapterInterface;

abstract readonly class BaseAdapterConfiguration
{
    abstract public function createAdapter(): StorageAdapterInterface;

    abstract public static function fromArray(array $data, string $basePath): static;

    abstract public function toArray(): array;

    public static function getType(): string
    {
        return AdapterConfigurationFactory::getTypeForClass(static::class);
    }

    protected static function resolvePath(string $path, string $basePath): string
    {
        if (str_starts_with($path, '/')) {
            return $path;
        }

        $resolved = rtrim($basePath, '/') . '/' . $path;

        // Normalize /./ sequences
        while (str_contains($resolved, '/./')) {
            $resolved = str_replace('/./', '/', $resolved);
        }

        return $resolved;
    }
}
