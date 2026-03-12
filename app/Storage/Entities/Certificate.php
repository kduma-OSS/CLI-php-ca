<?php

namespace App\Storage\Entities;

use App\Storage\Entity;

class Certificate extends Entity
{
    public function __construct(
        string $id,
        public readonly ?string $keyId,
        public readonly string $commonName,
        public readonly string $type,
        public readonly string $serialNumber,
        public readonly string $notBefore,
        public readonly string $notAfter,
        public readonly array $subjectAltNames = [],
        public readonly array $extensions = [],
        public readonly ?string $revokedAt = null,
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
            notBefore: $data['not_before'],
            notAfter: $data['not_after'],
            subjectAltNames: $data['subject_alt_names'] ?? [],
            extensions: $data['extensions'] ?? [],
            revokedAt: $data['revoked_at'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'common_name' => $this->commonName,
            'extensions' => $this->extensions,
            'key_id' => $this->keyId,
            'not_after' => $this->notAfter,
            'not_before' => $this->notBefore,
            'revoked_at' => $this->revokedAt,
            'serial_number' => $this->serialNumber,
            'subject_alt_names' => $this->subjectAltNames,
            'type' => $this->type,
        ];
    }
}
