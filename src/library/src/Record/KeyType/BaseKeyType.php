<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\KeyType;

abstract readonly class BaseKeyType
{
    abstract public function getType(): string;

    abstract public function toArray(): array;

    public static function fromArray(array $data): static
    {
        return match ($data['type']) {
            'rsa' => RSAKeyType::fromArray($data),
            'dsa' => DSAKeyType::fromArray($data),
            'ecdsa' => ECDSAKeyType::fromArray($data),
            'eddsa' => EdDSAKeyType::fromArray($data),
            default => throw new \InvalidArgumentException("Unknown key type: {$data['type']}"),
        };
    }
}
