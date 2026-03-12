<?php

namespace App\Storage\Entities;

use App\Storage\Entity;
use Carbon\CarbonImmutable;

class Key extends Entity
{
    public function __construct(
        string $id,
        public readonly ?int $size,
        public readonly string $fingerprint,
        public readonly CarbonImmutable $createdAt,
    ) {
        parent::__construct($id);
    }

    public static function fromArray(string $id, array $data): static
    {
        return new static(
            id: $id,
            size: $data['size'] ?? null,
            fingerprint: $data['fingerprint'],
            createdAt: CarbonImmutable::parse($data['created_at']),
        );
    }

    public function toArray(): array
    {
        return [
            'size' => $this->size,
            'fingerprint' => $this->fingerprint,
            'created_at' => $this->createdAt->toIso8601String(),
        ];
    }
}
