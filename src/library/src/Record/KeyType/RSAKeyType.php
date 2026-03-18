<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\KeyType;

readonly class RSAKeyType extends BaseKeyType
{
    public function __construct(
        public int $size,
    ) {}

    public function getType(): string
    {
        return 'rsa';
    }

    public function toArray(): array
    {
        return [
            'type' => $this->getType(),
            'size' => $this->size,
        ];
    }

    public static function fromArray(array $data): static
    {
        return new static(
            size: $data['size'],
        );
    }
}
