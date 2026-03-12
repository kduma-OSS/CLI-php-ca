<?php

namespace App\Config;

readonly class CertificateTemplateConfig
{
    public function __construct(
        public bool $ca,
        public ?int $pathLengthConstraint,
        public string $validity,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            ca: $data['ca'],
            pathLengthConstraint: $data['path_length_constraint'] ?? null,
            validity: $data['validity'],
        );
    }
}
