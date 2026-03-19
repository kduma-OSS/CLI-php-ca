<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\Extension\Extensions;

use KDuma\PhpCA\Record\Extension\BaseExtension;

readonly class AuthorityInfoAccessExtension extends BaseExtension
{
    /**
     * @param string[] $ocspUris
     * @param string[] $caIssuersUris
     */
    public function __construct(
        public array $ocspUris = [],
        public array $caIssuersUris = [],
        private bool $critical = false,
    ) {}

    public static function oid(): string
    {
        return '1.3.6.1.5.5.7.1.1';
    }

    public static function name(): string
    {
        return 'authority-info-access';
    }

    public function isCritical(): bool
    {
        return $this->critical;
    }

    public function toArray(): array
    {
        return [
            'name' => self::name(),
            'critical' => $this->critical,
            'ocsp_uris' => $this->ocspUris,
            'ca_issuers_uris' => $this->caIssuersUris,
        ];
    }

    public static function fromArray(array $data): static
    {
        return new static(
            ocspUris: $data['ocsp_uris'] ?? [],
            caIssuersUris: $data['ca_issuers_uris'] ?? [],
            critical: $data['critical'] ?? false,
        );
    }
}
