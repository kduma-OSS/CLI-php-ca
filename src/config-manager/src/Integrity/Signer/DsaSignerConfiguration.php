<?php

declare(strict_types=1);

namespace KDuma\PhpCA\ConfigManager\Integrity\Signer;

use KDuma\PhpCA\ConfigManager\Integrity\Signer\Attributes\SignerConfiguration;
use KDuma\PhpCA\ConfigManager\ValueProvider\ValueProvider;
use KDuma\PhpCA\ConfigManager\ValueProvider\ValueProviderFactory;
use KDuma\SimpleDAL\Integrity\Contracts\SigningAlgorithmInterface;
use KDuma\SimpleDAL\Integrity\PhpSecLib\DsaSigningAlgorithm;
use phpseclib3\Crypt\DSA;

#[SignerConfiguration('dsa')]
readonly class DsaSignerConfiguration extends BaseSignerConfiguration
{
    public function __construct(
        public string $id,
        public ValueProvider $key,
        public ?ValueProvider $password = null,
        public ?string $hash = null,
    ) {}

    public function createSigner(): SigningAlgorithmInterface
    {
        $pem = $this->key->resolve();
        $dsaKey = DSA::load($pem, $this->password?->resolve() ?? false);

        if ($this->hash !== null) {
            $dsaKey = $dsaKey->withHash($this->hash);
        }

        return new DsaSigningAlgorithm(
            id: $this->id,
            key: $dsaKey,
        );
    }

    public static function fromArray(array $data, string $basePath): static
    {
        $factory = new ValueProviderFactory;

        return new static(
            id: $data['id'] ?? throw new \InvalidArgumentException('DSA signer requires "id".'),
            key: $factory->fromArray($data['key'] ?? throw new \InvalidArgumentException('DSA signer requires "key".'), $basePath),
            password: isset($data['password']) ? $factory->fromArray($data['password'], $basePath) : null,
            hash: $data['hash'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'type' => static::getType(),
            'id' => $this->id,
            'key' => $this->key->toArray(),
            'password' => $this->password?->toArray(),
            'hash' => $this->hash,
        ], fn ($v) => $v !== null);
    }
}
