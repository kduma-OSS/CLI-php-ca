<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\Extension\Template\Templates;

use KDuma\PhpCA\Record\Extension\BaseExtension;
use KDuma\PhpCA\Record\Extension\Extensions\CertificatePoliciesExtension;
use KDuma\PhpCA\Record\Extension\Resolver\ExtensionValueResolverFactory;
use KDuma\PhpCA\Record\Extension\Resolver\IssuanceContext;
use KDuma\PhpCA\Record\Extension\Template\BaseExtensionTemplate;

class CertificatePoliciesExtensionTemplate extends BaseExtensionTemplate
{
    /**
     * @param array $policies Each: ['oid' => string, 'cps' => Resolver|string|null, 'notice' => Resolver|string|null, 'noticeRef' => ['organization' => Resolver|string, 'noticeNumbers' => int[]]|null]
     */
    public function __construct(
        public readonly array $policies = [],
        public readonly bool $critical = false,
    ) {}

    public static function name(): string
    {
        return 'certificate-policies';
    }

    public function resolve(IssuanceContext $context): BaseExtension
    {
        $resolved = array_map(function (array $policy) use ($context): array {
            $result = ['oid' => $policy['oid']];

            if (isset($policy['cps'])) {
                $result['cps'] = ExtensionValueResolverFactory::resolveField($policy['cps'], $context);
            }

            if (isset($policy['notice'])) {
                $result['notice'] = ExtensionValueResolverFactory::resolveField($policy['notice'], $context);
            }

            if (isset($policy['noticeRef'])) {
                $result['noticeRef'] = [
                    'organization' => ExtensionValueResolverFactory::resolveField($policy['noticeRef']['organization'], $context),
                    'noticeNumbers' => $policy['noticeRef']['noticeNumbers'],
                ];
            }

            return $result;
        }, $this->policies);

        return new CertificatePoliciesExtension(
            policies: $resolved,
            critical: $this->critical,
        );
    }

    public function isCritical(): bool
    {
        return $this->critical;
    }

    public function toArray(): array
    {
        $policies = array_map(function (array $policy): array {
            $result = ['oid' => $policy['oid']];

            if (isset($policy['cps'])) {
                $result['cps'] = ExtensionValueResolverFactory::toMixed($policy['cps']);
            }

            if (isset($policy['notice'])) {
                $result['notice'] = ExtensionValueResolverFactory::toMixed($policy['notice']);
            }

            if (isset($policy['noticeRef'])) {
                $result['noticeRef'] = [
                    'organization' => ExtensionValueResolverFactory::toMixed($policy['noticeRef']['organization']),
                    'noticeNumbers' => $policy['noticeRef']['noticeNumbers'],
                ];
            }

            return $result;
        }, $this->policies);

        return [
            'name' => self::name(),
            'critical' => $this->critical,
            'policies' => $policies,
        ];
    }

    public static function fromArray(array $data): static
    {
        if (isset($data['critical']) && ! is_bool($data['critical'])) {
            throw new \InvalidArgumentException('certificate-policies: "critical" must be a boolean.');
        }

        if (isset($data['policies']) && ! is_array($data['policies'])) {
            throw new \InvalidArgumentException('certificate-policies: "policies" must be an array.');
        }

        $policies = [];

        foreach (($data['policies'] ?? []) as $i => $policy) {
            if (is_string($policy)) {
                $policies[] = ['oid' => $policy];
                continue;
            }

            if (! is_array($policy) || ! isset($policy['oid'])) {
                throw new \InvalidArgumentException("certificate-policies: policy at index {$i} must have an \"oid\".");
            }

            $parsed = ['oid' => $policy['oid']];

            if (isset($policy['cps'])) {
                $parsed['cps'] = ExtensionValueResolverFactory::fromMixed($policy['cps']);
            }

            if (isset($policy['notice'])) {
                $parsed['notice'] = ExtensionValueResolverFactory::fromMixed($policy['notice']);
            }

            if (isset($policy['noticeRef'])) {
                if (! is_array($policy['noticeRef']) || ! isset($policy['noticeRef']['organization'])) {
                    throw new \InvalidArgumentException("certificate-policies: noticeRef at policy {$i} must have an \"organization\".");
                }

                $parsed['noticeRef'] = [
                    'organization' => ExtensionValueResolverFactory::fromMixed($policy['noticeRef']['organization']),
                    'noticeNumbers' => $policy['noticeRef']['noticeNumbers'] ?? [],
                ];
            }

            $policies[] = $parsed;
        }

        return new static(
            policies: $policies,
            critical: $data['critical'] ?? false,
        );
    }
}
