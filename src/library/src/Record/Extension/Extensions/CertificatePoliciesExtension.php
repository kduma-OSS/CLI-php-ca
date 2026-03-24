<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\Extension\Extensions;

use KDuma\PhpCA\Record\Extension\BaseExtension;

readonly class CertificatePoliciesExtension extends BaseExtension
{
    /**
     * @param  array<array{oid: string, cps?: ?string, notice?: ?string, noticeRef?: ?array{organization: string, noticeNumbers: int[]}}>  $policies
     */
    public function __construct(
        public array $policies,
        private bool $critical = false,
    ) {}

    public static function oid(): string
    {
        return '2.5.29.32';
    }

    public static function name(): string
    {
        return 'certificate-policies';
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
            'policies' => array_map(fn (array $policy) => array_filter([
                'oid' => $policy['oid'],
                'cps' => $policy['cps'] ?? null,
                'notice' => $policy['notice'] ?? null,
                'noticeRef' => $policy['noticeRef'] ?? null,
            ], fn ($v) => $v !== null), $this->policies),
        ];
    }

    public static function fromArray(array $data): static
    {
        $policies = array_map(function (string|array $policy): array {
            if (is_string($policy)) {
                return ['oid' => $policy];
            }

            return array_filter([
                'oid' => $policy['oid'],
                'cps' => $policy['cps'] ?? null,
                'notice' => $policy['notice'] ?? null,
                'noticeRef' => $policy['noticeRef'] ?? null,
            ], fn ($v) => $v !== null);
        }, $data['policies'] ?? []);

        return new static(
            policies: $policies,
            critical: $data['critical'] ?? false,
        );
    }
}
