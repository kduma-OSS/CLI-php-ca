<?php

declare(strict_types=1);

namespace KDuma\PhpCA\ConfigManager\Encryption\Algorithm;

use KDuma\PhpCA\ConfigManager\Encryption\Algorithm\Attributes\EncryptionAlgorithmConfiguration;
use KDuma\PhpCA\ConfigManager\ValueProvider\ValueProvider;
use KDuma\PhpCA\ConfigManager\ValueProvider\ValueProviderFactory;
use KDuma\SimpleDAL\Encryption\Contracts\EncryptionAlgorithmInterface;
use KDuma\SimpleDAL\Encryption\Sodium\SealedBoxAlgorithm;

#[EncryptionAlgorithmConfiguration('sealed-box')]
readonly class SealedBoxAlgorithmConfiguration extends BaseEncryptionAlgorithmConfiguration
{
    public function __construct(
        string $id,
        public ValueProvider $publicKey,
        public ?ValueProvider $secretKey = null,
    ) {
        parent::__construct($id);
    }

    public function createAlgorithm(): EncryptionAlgorithmInterface
    {
        return new SealedBoxAlgorithm(
            id: $this->id,
            publicKey: $this->publicKey->resolve(),
            secretKey: $this->secretKey?->resolve(),
        );
    }

    public static function fromArray(array $data, string $basePath): static
    {
        $factory = new ValueProviderFactory();

        return new static(
            id: $data['id'] ?? throw new \InvalidArgumentException('SealedBox requires "id".'),
            publicKey: $factory->fromArray($data['public_key'] ?? throw new \InvalidArgumentException('SealedBox requires "public_key".'), $basePath),
            secretKey: isset($data['secret_key']) ? $factory->fromArray($data['secret_key'], $basePath) : null,
        );
    }

    public function toArray(): array
    {
        $result = [
            'type' => static::getType(),
            'id' => $this->id,
            'public_key' => $this->publicKey->toArray(),
        ];

        if ($this->secretKey !== null) {
            $result['secret_key'] = $this->secretKey->toArray();
        }

        return $result;
    }
}
