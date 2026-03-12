<?php

namespace App\Storage\Entities;

use App\Storage\Entity;

class Key extends Entity
{
    public function __construct(
        string $id,
        public readonly string $algorithm,
        public readonly ?int $size,
        public readonly string $createdAt,
    ) {
        parent::__construct($id);
    }

    public static function fromArray(string $id, array $data): static
    {
        return new static(
            id: $id,
            algorithm: $data['algorithm'],
            size: $data['size'] ?? null,
            createdAt: $data['created_at'],
        );
    }

    public function toArray(): array
    {
        return [
            'algorithm' => $this->algorithm,
            'size' => $this->size,
            'created_at' => $this->createdAt,
        ];
    }
}
