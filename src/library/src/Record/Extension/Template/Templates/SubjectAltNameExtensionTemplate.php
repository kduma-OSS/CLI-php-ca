<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\Extension\Template\Templates;

use KDuma\PhpCA\Record\Extension\BaseExtension;
use KDuma\PhpCA\Record\Extension\Extensions\SubjectAltNameExtension;
use KDuma\PhpCA\Record\Extension\Resolver\ExtensionValueResolverFactory;
use KDuma\PhpCA\Record\Extension\Resolver\IssuanceContext;
use KDuma\PhpCA\Record\Extension\Template\BaseExtensionTemplate;

class SubjectAltNameExtensionTemplate extends BaseExtensionTemplate
{
    public function __construct(
        public readonly mixed $dnsNames = [],
        public readonly mixed $ipAddresses = [],
        public readonly mixed $emails = [],
        public readonly mixed $uris = [],
        public readonly bool $critical = false,
    ) {}

    public static function name(): string
    {
        return 'subject-alt-name';
    }

    public function resolve(IssuanceContext $context): BaseExtension
    {
        return new SubjectAltNameExtension(
            dnsNames: ExtensionValueResolverFactory::resolveField($this->dnsNames, $context),
            ipAddresses: ExtensionValueResolverFactory::resolveField($this->ipAddresses, $context),
            emails: ExtensionValueResolverFactory::resolveField($this->emails, $context),
            uris: ExtensionValueResolverFactory::resolveField($this->uris, $context),
            critical: $this->critical,
        );
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
            'dns_names' => ExtensionValueResolverFactory::toMixed($this->dnsNames),
            'ip_addresses' => ExtensionValueResolverFactory::toMixed($this->ipAddresses),
            'emails' => ExtensionValueResolverFactory::toMixed($this->emails),
            'uris' => ExtensionValueResolverFactory::toMixed($this->uris),
        ];
    }

    public static function fromArray(array $data): static
    {
        if (isset($data['critical']) && ! is_bool($data['critical'])) {
            throw new \InvalidArgumentException('subject-alt-name: "critical" must be a boolean.');
        }

        foreach (['dns_names', 'ip_addresses', 'emails', 'uris'] as $field) {
            if (isset($data[$field]) && ! is_array($data[$field]) && ! is_string($data[$field])) {
                throw new \InvalidArgumentException("subject-alt-name: \"{$field}\" must be an array or resolver object.");
            }
        }

        return new static(
            dnsNames: isset($data['dns_names']) ? ExtensionValueResolverFactory::fromMixed($data['dns_names']) : [],
            ipAddresses: isset($data['ip_addresses']) ? ExtensionValueResolverFactory::fromMixed($data['ip_addresses']) : [],
            emails: isset($data['emails']) ? ExtensionValueResolverFactory::fromMixed($data['emails']) : [],
            uris: isset($data['uris']) ? ExtensionValueResolverFactory::fromMixed($data['uris']) : [],
            critical: $data['critical'] ?? false,
        );
    }
}
