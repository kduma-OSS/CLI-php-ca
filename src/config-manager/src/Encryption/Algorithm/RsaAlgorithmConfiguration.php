<?php

declare(strict_types=1);

namespace KDuma\PhpCA\ConfigManager\Encryption\Algorithm;

use KDuma\PhpCA\ConfigManager\Encryption\Algorithm\Attributes\EncryptionAlgorithmConfiguration;
use KDuma\PhpCA\ConfigManager\ValueProvider\ValueProvider;
use KDuma\PhpCA\ConfigManager\ValueProvider\ValueProviderFactory;
use KDuma\SimpleDAL\Encryption\Contracts\EncryptionAlgorithmInterface;
use KDuma\SimpleDAL\Encryption\PhpSecLib\RsaAlgorithm;
use phpseclib3\Crypt\RSA;

#[EncryptionAlgorithmConfiguration('rsa')]
readonly class RsaAlgorithmConfiguration extends BaseEncryptionAlgorithmConfiguration
{
    public function __construct(
        string $id,
        public ValueProvider $key,
        public ?ValueProvider $password = null,
        public ?string $padding = null,
        public ?string $hash = null,
        public ?string $mgfHash = null,
    ) {
        parent::__construct($id);
    }

    public function createAlgorithm(): EncryptionAlgorithmInterface
    {
        $pem = $this->key->resolve();
        $rsaKey = RSA::load($pem, $this->password?->resolve() ?? false);

        if ($this->padding !== null) {
            $paddingConstant = match ($this->padding) {
                'oaep' => RSA::ENCRYPTION_OAEP,
                'pkcs1' => RSA::ENCRYPTION_PKCS1,
                'none' => RSA::ENCRYPTION_NONE,
                default => throw new \InvalidArgumentException("Unknown RSA encryption padding: {$this->padding}"),
            };
            $rsaKey = $rsaKey->withPadding($paddingConstant);
        }

        if ($this->hash !== null) {
            $rsaKey = $rsaKey->withHash($this->hash);
        }

        if ($this->mgfHash !== null) {
            $rsaKey = $rsaKey->withMGFHash($this->mgfHash);
        }

        return new RsaAlgorithm(
            id: $this->id,
            key: $rsaKey,
        );
    }

    public static function fromArray(array $data, string $basePath): static
    {
        $factory = new ValueProviderFactory;

        return new static(
            id: $data['id'] ?? throw new \InvalidArgumentException('RSA encryption requires "id".'),
            key: $factory->fromArray($data['key'] ?? throw new \InvalidArgumentException('RSA encryption requires "key".'), $basePath),
            password: isset($data['password']) ? $factory->fromArray($data['password'], $basePath) : null,
            padding: $data['padding'] ?? null,
            hash: $data['hash'] ?? null,
            mgfHash: $data['mgf_hash'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'type' => static::getType(),
            'id' => $this->id,
            'key' => $this->key->toArray(),
            'password' => $this->password?->toArray(),
            'padding' => $this->padding,
            'hash' => $this->hash,
            'mgf_hash' => $this->mgfHash,
        ], fn ($v) => $v !== null);
    }
}
