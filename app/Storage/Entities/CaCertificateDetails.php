<?php

namespace App\Storage\Entities;

class CaCertificateDetails
{
    public function __construct(
        public readonly string $serial_number,
        public readonly string $distinguished_name,
        public readonly ?int $path_length_constraint = null,
    ) {}

    public static function fromArray(array $data): static
    {
        return new static(
            serial_number: $data['serial_number'],
            distinguished_name: $data['distinguished_name'],
            path_length_constraint: $data['path_length_constraint'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'serial_number' => $this->serial_number,
            'distinguished_name' => $this->distinguished_name,
            'path_length_constraint' => $this->path_length_constraint,
        ];
    }
}
