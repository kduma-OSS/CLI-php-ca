<?php

declare(strict_types=1);

namespace KDuma\PhpCA\ConfigManager\Encryption\Algorithm;

use KDuma\PhpCA\ConfigManager\Encryption\Algorithm\Attributes\EncryptionAlgorithmConfiguration;
use KDuma\PhpCA\ConfigManager\ValueProvider\ValueProvider;
use KDuma\PhpCA\ConfigManager\ValueProvider\ValueProviderFactory;
use KDuma\SimpleDAL\Encryption\Contracts\EncryptionAlgorithmInterface;
use KDuma\SimpleDAL\Encryption\Sodium\SecretBoxAlgorithm;

#[EncryptionAlgorithmConfiguration('secret-box')]
readonly class SecretBoxAlgorithmConfiguration extends BaseEncryptionAlgorithmConfiguration
{
    public function __construct(
        string $id,
        public ValueProvider $key,
    ) {
        parent::__construct($id);
    }

    public function createAlgorithm(): EncryptionAlgorithmInterface
    {
        return new SecretBoxAlgorithm(
            id: $this->id,
            key: $this->key->resolve(),
        );
    }

    public static function fromArray(array $data, string $basePath): static
    {
        $factory = new ValueProviderFactory;

        return new static(
            id: $data['id'] ?? throw new \InvalidArgumentException('SecretBox requires "id".'),
            key: $factory->fromArray($data['key'] ?? throw new \InvalidArgumentException('SecretBox requires "key".'), $basePath),
        );
    }

    public function toArray(): array
    {
        return [
            'type' => static::getType(),
            'id' => $this->id,
            'key' => $this->key->toArray(),
        ];
    }
}
