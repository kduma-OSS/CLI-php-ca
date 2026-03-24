<?php

declare(strict_types=1);

namespace KDuma\PhpCA\ConfigManager\Integrity\Signer;

use KDuma\PhpCA\ConfigManager\Integrity\Signer\Attributes\SignerConfiguration;
use KDuma\PhpCA\ConfigManager\ValueProvider\ValueProvider;
use KDuma\PhpCA\ConfigManager\ValueProvider\ValueProviderFactory;
use KDuma\SimpleDAL\Integrity\Contracts\SigningAlgorithmInterface;
use KDuma\SimpleDAL\Integrity\Hash\Signer\HmacSha256SigningAlgorithm;

#[SignerConfiguration('hmac-sha256')]
readonly class HmacSha256SignerConfiguration extends BaseSignerConfiguration
{
    public function __construct(
        public string $id,
        public ValueProvider $secret,
    ) {}

    public function createSigner(): SigningAlgorithmInterface
    {
        return new HmacSha256SigningAlgorithm(
            id: $this->id,
            secret: $this->secret->resolve(),
        );
    }

    public static function fromArray(array $data, string $basePath): static
    {
        $factory = new ValueProviderFactory;

        return new static(
            id: $data['id'] ?? throw new \InvalidArgumentException('HMAC signer requires "id".'),
            secret: $factory->fromArray($data['secret'] ?? throw new \InvalidArgumentException('HMAC signer requires "secret".'), $basePath),
        );
    }

    public function toArray(): array
    {
        return [
            'type' => static::getType(),
            'id' => $this->id,
            'secret' => $this->secret->toArray(),
        ];
    }
}
