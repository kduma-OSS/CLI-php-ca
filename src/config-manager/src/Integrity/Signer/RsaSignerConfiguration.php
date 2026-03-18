<?php

declare(strict_types=1);

namespace KDuma\PhpCA\ConfigManager\Integrity\Signer;

use KDuma\PhpCA\ConfigManager\Integrity\Signer\Attributes\SignerConfiguration;
use KDuma\PhpCA\ConfigManager\ValueProvider\ValueProvider;
use KDuma\PhpCA\ConfigManager\ValueProvider\ValueProviderFactory;
use KDuma\SimpleDAL\Integrity\Contracts\SigningAlgorithmInterface;
use KDuma\SimpleDAL\Integrity\PhpSecLib\RsaSigningAlgorithm;
use phpseclib3\Crypt\RSA;

#[SignerConfiguration('rsa')]
readonly class RsaSignerConfiguration extends BaseSignerConfiguration
{
    public function __construct(
        public string $id,
        public ValueProvider $key,
        public ?ValueProvider $password = null,
        public ?string $padding = null,
        public ?string $hash = null,
        public ?string $mgfHash = null,
        public ?int $saltLength = null,
    ) {}

    public function createSigner(): SigningAlgorithmInterface
    {
        $pem = $this->key->resolve();
        $rsaKey = RSA::load($pem, $this->password?->resolve() ?? false);

        if ($this->padding !== null) {
            $paddingConstant = match ($this->padding) {
                'pss' => RSA::SIGNATURE_PSS,
                'pkcs1' => RSA::SIGNATURE_PKCS1,
                default => throw new \InvalidArgumentException("Unknown RSA signing padding: {$this->padding}"),
            };
            $rsaKey = $rsaKey->withPadding($paddingConstant);
        }

        if ($this->hash !== null) {
            $rsaKey = $rsaKey->withHash($this->hash);
        }

        if ($this->mgfHash !== null) {
            $rsaKey = $rsaKey->withMGFHash($this->mgfHash);
        }

        if ($this->saltLength !== null) {
            $rsaKey = $rsaKey->withSaltLength($this->saltLength);
        }

        return new RsaSigningAlgorithm(
            id: $this->id,
            key: $rsaKey,
        );
    }

    public static function fromArray(array $data, string $basePath): static
    {
        $factory = new ValueProviderFactory();

        return new static(
            id: $data['id'] ?? throw new \InvalidArgumentException('RSA signer requires "id".'),
            key: $factory->fromArray($data['key'] ?? throw new \InvalidArgumentException('RSA signer requires "key".'), $basePath),
            password: isset($data['password']) ? $factory->fromArray($data['password'], $basePath) : null,
            padding: $data['padding'] ?? null,
            hash: $data['hash'] ?? null,
            mgfHash: $data['mgf_hash'] ?? null,
            saltLength: $data['salt_length'] ?? null,
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
            'salt_length' => $this->saltLength,
        ], fn ($v) => $v !== null);
    }
}
