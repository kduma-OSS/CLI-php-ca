<?php

declare(strict_types=1);

namespace KDuma\PhpCA\ConfigManager\Integrity\Signer;

use KDuma\PhpCA\ConfigManager\Integrity\Signer\Attributes\SignerConfiguration;
use KDuma\PhpCA\ConfigManager\ValueProvider\ValueProvider;
use KDuma\PhpCA\ConfigManager\ValueProvider\ValueProviderFactory;
use KDuma\SimpleDAL\Integrity\Contracts\SigningAlgorithmInterface;
use KDuma\SimpleDAL\Integrity\PhpSecLib\EcSigningAlgorithm;
use phpseclib3\Crypt\EC;

#[SignerConfiguration('ec')]
readonly class EcSignerConfiguration extends BaseSignerConfiguration
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
        $ecKey = EC::load($pem, $this->password?->resolve() ?? false);

        if ($this->hash !== null) {
            $ecKey = $ecKey->withHash($this->hash);
        }

        return new EcSigningAlgorithm(
            id: $this->id,
            key: $ecKey,
        );
    }

    public static function fromArray(array $data, string $basePath): static
    {
        $factory = new ValueProviderFactory;

        return new static(
            id: $data['id'] ?? throw new \InvalidArgumentException('EC signer requires "id".'),
            key: $factory->fromArray($data['key'] ?? throw new \InvalidArgumentException('EC signer requires "key".'), $basePath),
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
