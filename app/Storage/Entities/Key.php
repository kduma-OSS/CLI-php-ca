<?php

namespace App\Storage\Entities;

use App\Storage\Infrastructure\Entity;
use Carbon\CarbonImmutable;

class Key extends Entity
{
    public function __construct(
        string $id,
        public readonly ?int $size,
        public readonly string $fingerprint,
        public readonly CarbonImmutable $createdAt,
        public readonly bool $private = true,
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
            private: $data['private'] ?? true,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'created_at' => $this->createdAt->toIso8601String(),
            'fingerprint' => $this->fingerprint,
            'private' => $this->private ? null : false,
            'size' => $this->size,
        ], fn ($v) => $v !== null);
    }
}
