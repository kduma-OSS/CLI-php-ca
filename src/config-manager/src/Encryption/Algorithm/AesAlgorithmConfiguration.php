<?php

declare(strict_types=1);

namespace KDuma\PhpCA\ConfigManager\Encryption\Algorithm;

use KDuma\PhpCA\ConfigManager\Encryption\Algorithm\Attributes\EncryptionAlgorithmConfiguration;
use KDuma\PhpCA\ConfigManager\ValueProvider\ValueProvider;
use KDuma\PhpCA\ConfigManager\ValueProvider\ValueProviderFactory;
use KDuma\SimpleDAL\Encryption\Contracts\EncryptionAlgorithmInterface;
use KDuma\SimpleDAL\Encryption\PhpSecLib\AesAlgorithm;
use phpseclib3\Crypt\AES;

#[EncryptionAlgorithmConfiguration('aes')]
readonly class AesAlgorithmConfiguration extends BaseEncryptionAlgorithmConfiguration
{
    public function __construct(
        string $id,
        public ValueProvider $key,
        public string $mode = 'ctr',
    ) {
        parent::__construct($id);
    }

    public function createAlgorithm(): EncryptionAlgorithmInterface
    {
        $cipher = new AES($this->mode);
        $cipher->setKey($this->key->resolve());

        return new AesAlgorithm(
            id: $this->id,
            cipher: $cipher,
        );
    }

    public static function fromArray(array $data, string $basePath): static
    {
        $factory = new ValueProviderFactory;

        return new static(
            id: $data['id'] ?? throw new \InvalidArgumentException('AES requires "id".'),
            key: $factory->fromArray($data['key'] ?? throw new \InvalidArgumentException('AES requires "key".'), $basePath),
            mode: $data['mode'] ?? 'ctr',
        );
    }

    public function toArray(): array
    {
        return [
            'type' => static::getType(),
            'id' => $this->id,
            'key' => $this->key->toArray(),
            'mode' => $this->mode,
        ];
    }
}
