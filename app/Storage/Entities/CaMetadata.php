<?php

namespace App\Storage\Entities;

use App\Storage\Infrastructure\SingletonEntity;

class CaMetadata extends SingletonEntity
{
    public function __construct(
        public readonly ?string $key_id = null,
    ) {}

    public static function fromArray(array $data): static
    {
        return new static(
            key_id: $data['key_id'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'key_id' => $this->key_id,
        ];
    }
}
