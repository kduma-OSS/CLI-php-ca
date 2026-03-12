<?php

namespace App\Storage\Entities;

use App\Storage\Entity;
use Carbon\CarbonImmutable;

class Certificate extends Entity
{
    public function __construct(
        string $id,
        public readonly ?string $keyId,
        public readonly string $commonName,
        public readonly string $type,
        public readonly string $serialNumber,
        public readonly CarbonImmutable $notBefore,
        public readonly CarbonImmutable $notAfter,
        public readonly array $subjectAltNames = [],
        public readonly array $extensions = [],
        public readonly ?CarbonImmutable $revokedAt = null,
    ) {
        parent::__construct($id);
    }

    public static function fromArray(string $id, array $data): static
    {
        return new static(
            id: $id,
            keyId: $data['key_id'] ?? null,
            commonName: $data['common_name'],
            type: $data['type'],
            serialNumber: $data['serial_number'],
            notBefore: CarbonImmutable::parse($data['not_before']),
            notAfter: CarbonImmutable::parse($data['not_after']),
            subjectAltNames: $data['subject_alt_names'] ?? [],
            extensions: $data['extensions'] ?? [],
            revokedAt: isset($data['revoked_at']) ? CarbonImmutable::parse($data['revoked_at']) : null,
        );
    }

    public function toArray(): array
    {
        return [
            'common_name' => $this->commonName,
            'extensions' => $this->extensions,
            'key_id' => $this->keyId,
            'not_after' => $this->notAfter->toIso8601String(),
            'not_before' => $this->notBefore->toIso8601String(),
            'revoked_at' => $this->revokedAt?->toIso8601String(),
            'serial_number' => $this->serialNumber,
            'subject_alt_names' => $this->subjectAltNames,
            'type' => $this->type,
        ];
    }
}
