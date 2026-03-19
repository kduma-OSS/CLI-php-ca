<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\Extension\Extensions;

use KDuma\PhpCA\Record\Extension\BaseExtension;

readonly class SubjectAltNameExtension extends BaseExtension
{
    /**
     * @param string[] $dnsNames
     * @param string[] $ipAddresses
     * @param string[] $emails
     * @param string[] $uris
     */
    public function __construct(
        public array $dnsNames = [],
        public array $ipAddresses = [],
        public array $emails = [],
        public array $uris = [],
        private bool $critical = false,
    ) {}

    public static function oid(): string
    {
        return '2.5.29.17';
    }

    public static function name(): string
    {
        return 'subject-alt-name';
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
            'dns_names' => $this->dnsNames,
            'ip_addresses' => $this->ipAddresses,
            'emails' => $this->emails,
            'uris' => $this->uris,
        ];
    }

    public static function fromArray(array $data): static
    {
        return new static(
            dnsNames: $data['dns_names'] ?? [],
            ipAddresses: $data['ip_addresses'] ?? [],
            emails: $data['emails'] ?? [],
            uris: $data['uris'] ?? [],
            critical: $data['critical'] ?? false,
        );
    }
}
