<?php

declare(strict_types=1);

namespace KDuma\PhpCA\ConfigManager\ValueProvider;

use KDuma\PhpCA\ConfigManager\Adapter\BaseAdapterConfiguration;
use KDuma\PhpCA\ConfigManager\ValueProvider\Attributes\ValueProviderType;

#[ValueProviderType('file')]
readonly class FileValueProvider extends ValueProvider
{
    public function __construct(
        public string $path,
    ) {}

    public function resolve(): string
    {
        if (! file_exists($this->path)) {
            throw new \RuntimeException("Key file not found: {$this->path}");
        }

        return file_get_contents($this->path)
            ?: throw new \RuntimeException("Failed to read key file: {$this->path}");
    }

    public static function fromArray(array $data, string $basePath): static
    {
        $path = $data['path'] ?? throw new \InvalidArgumentException('File key discovery requires "path".');

        return new static(
            path: self::resolvePath($path, $basePath),
        );
    }

    public function toArray(): array
    {
        return [
            'type' => static::getType(),
            'path' => $this->path,
        ];
    }

    private static function resolvePath(string $path, string $basePath): string
    {
        if (str_starts_with($path, '/')) {
            return $path;
        }

        $resolved = rtrim($basePath, '/') . '/' . $path;

        while (str_contains($resolved, '/./')) {
            $resolved = str_replace('/./', '/', $resolved);
        }

        return $resolved;
    }
}
