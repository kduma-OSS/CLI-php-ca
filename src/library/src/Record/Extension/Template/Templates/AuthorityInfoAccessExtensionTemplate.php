<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\Extension\Template\Templates;

use KDuma\PhpCA\Record\Extension\BaseExtension;
use KDuma\PhpCA\Record\Extension\Extensions\AuthorityInfoAccessExtension;
use KDuma\PhpCA\Record\Extension\Resolver\ExtensionValueResolverFactory;
use KDuma\PhpCA\Record\Extension\Resolver\IssuanceContext;
use KDuma\PhpCA\Record\Extension\Template\BaseExtensionTemplate;

class AuthorityInfoAccessExtensionTemplate extends BaseExtensionTemplate
{
    public function __construct(
        public readonly mixed $ocspUris = [],
        public readonly mixed $caIssuersUris = [],
        public readonly bool $critical = false,
    ) {}

    public static function name(): string
    {
        return 'authority-info-access';
    }

    public function resolve(IssuanceContext $context): BaseExtension
    {
        return new AuthorityInfoAccessExtension(
            ocspUris: ExtensionValueResolverFactory::resolveField($this->ocspUris, $context),
            caIssuersUris: ExtensionValueResolverFactory::resolveField($this->caIssuersUris, $context),
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
            'ocsp_uris' => ExtensionValueResolverFactory::toMixed($this->ocspUris),
            'ca_issuers_uris' => ExtensionValueResolverFactory::toMixed($this->caIssuersUris),
        ];
    }

    public static function fromArray(array $data): static
    {
        if (isset($data['critical']) && ! is_bool($data['critical'])) {
            throw new \InvalidArgumentException('authority-info-access: "critical" must be a boolean.');
        }

        foreach (['ocsp_uris', 'ca_issuers_uris'] as $field) {
            if (isset($data[$field]) && ! is_array($data[$field])) {
                throw new \InvalidArgumentException("authority-info-access: \"{$field}\" must be an array.");
            }
        }

        return new static(
            ocspUris: isset($data['ocsp_uris']) ? ExtensionValueResolverFactory::fromMixed($data['ocsp_uris']) : [],
            caIssuersUris: isset($data['ca_issuers_uris']) ? ExtensionValueResolverFactory::fromMixed($data['ca_issuers_uris']) : [],
            critical: $data['critical'] ?? false,
        );
    }
}
