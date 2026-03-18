<?php

declare(strict_types=1);

namespace KDuma\PhpCA\ConfigManager\Adapter;

use KDuma\PhpCA\ConfigManager\Adapter\Attributes\AdapterConfiguration;
use KDuma\SimpleDAL\Adapter\Contracts\StorageAdapterInterface;
use KDuma\SimpleDAL\Adapter\Flysystem\FlysystemAdapter;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;

#[AdapterConfiguration('directory')]
readonly class DirectoryAdapterConfiguration extends BaseAdapterConfiguration
{
    public function __construct(
        public string $path,
    ) {}

    public static function fromArray(array $data, string $basePath): static
    {
        return new static(
            path: self::resolvePath($data['path'], $basePath),
        );
    }

    public function toArray(): array
    {
        return [
            'type' => static::getType(),
            'path' => $this->path,
        ];
    }

    public function createAdapter(): StorageAdapterInterface
    {
        return new FlysystemAdapter(
            new Filesystem(new LocalFilesystemAdapter($this->path)),
        );
    }
}
