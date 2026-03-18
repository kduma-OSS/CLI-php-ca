<?php

declare(strict_types=1);

namespace KDuma\PhpCA\ConfigManager\Adapter;

use KDuma\PhpCA\ConfigManager\Adapter\Attributes\AdapterConfiguration;
use KDuma\SimpleDAL\Adapter\Contracts\StorageAdapterInterface;
use RuntimeException;

#[AdapterConfiguration('mysql')]
readonly class MySqlAdapterConfiguration extends BaseAdapterConfiguration
{
    public function __construct(
        public string $host,
        public int $port,
        public string $database,
        public string $username,
        public string $password,
    ) {}

    public static function fromArray(array $data, string $basePath): static
    {
        return new static(
            host: $data['host'] ?? '127.0.0.1',
            port: $data['port'] ?? 3306,
            database: $data['database'] ?? throw new \InvalidArgumentException('MySQL adapter requires "database" option.'),
            username: $data['username'] ?? 'root',
            password: $data['password'] ?? '',
        );
    }

    public function toArray(): array
    {
        return [
            'type' => static::getType(),
            'host' => $this->host,
            'port' => $this->port,
            'database' => $this->database,
            'username' => $this->username,
            'password' => $this->password,
        ];
    }

    public function createAdapter(): StorageAdapterInterface
    {
        throw new RuntimeException('MySQL adapter is not yet implemented.');
    }
}
