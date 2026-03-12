<?php

namespace App\Config;

readonly class CertificationAuthorityConfig
{
    public function __construct(
        public bool $randomSerialNumbers,
        public string $distinguishedName,
        public array $certificateDistributionPoints,
        public array $crlDistributionPoints,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            randomSerialNumbers: $data['random_serial_numbers'],
            distinguishedName: $data['distinguished_name'],
            certificateDistributionPoints: $data['certificate_distribution_points'] ?? [],
            crlDistributionPoints: $data['crl_distribution_points'] ?? [],
        );
    }
}
