<?php

declare(strict_types=1);

namespace KDuma\PhpCA\ConfigManager\Integrity\Signer;

use KDuma\PhpCA\ConfigManager\Integrity\Signer\Attributes\SignerConfiguration;
use KDuma\PhpCA\ConfigManager\ValueProvider\ValueProvider;
use KDuma\PhpCA\ConfigManager\ValueProvider\ValueProviderFactory;
use KDuma\SimpleDAL\Integrity\Contracts\SigningAlgorithmInterface;
use KDuma\SimpleDAL\Integrity\Sodium\Ed25519SigningAlgorithm;

#[SignerConfiguration('ed25519')]
readonly class Ed25519SignerConfiguration extends BaseSignerConfiguration
{
    public function __construct(
        public string $id,
        public ValueProvider $publicKey,
        public ?ValueProvider $secretKey = null,
    ) {}

    public function createSigner(): SigningAlgorithmInterface
    {
        return new Ed25519SigningAlgorithm(
            id: $this->id,
            secretKey: $this->secretKey?->resolve(),
            publicKey: $this->publicKey->resolve(),
        );
    }

    public static function fromArray(array $data, string $basePath): static
    {
        $factory = new ValueProviderFactory();

        return new static(
            id: $data['id'] ?? throw new \InvalidArgumentException('Ed25519 signer requires "id".'),
            publicKey: $factory->fromArray($data['public_key'] ?? throw new \InvalidArgumentException('Ed25519 signer requires "public_key".'), $basePath),
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
