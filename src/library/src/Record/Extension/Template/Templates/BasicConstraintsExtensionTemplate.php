<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\Extension\Template\Templates;

use KDuma\PhpCA\Record\Extension\BaseExtension;
use KDuma\PhpCA\Record\Extension\Extensions\BasicConstraintsExtension;
use KDuma\PhpCA\Record\Extension\Resolver\IssuanceContext;
use KDuma\PhpCA\Record\Extension\Template\BaseExtensionTemplate;

class BasicConstraintsExtensionTemplate extends BaseExtensionTemplate
{
    public function __construct(
        public readonly bool $ca = false,
        public readonly ?int $pathLenConstraint = null,
        public readonly bool $critical = true,
    ) {}

    public static function name(): string
    {
        return 'basic-constraints';
    }

    public function resolve(IssuanceContext $context): BaseExtension
    {
        return new BasicConstraintsExtension(
            ca: $this->ca,
            pathLenConstraint: $this->pathLenConstraint,
            critical: $this->critical,
        );
    }

    public function isCritical(): bool
    {
        return $this->critical;
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => self::name(),
            'critical' => $this->critical,
            'ca' => $this->ca,
            'path_len_constraint' => $this->pathLenConstraint,
        ], fn ($v) => $v !== null);
    }

    public static function fromArray(array $data): static
    {
        if (isset($data['ca']) && ! is_bool($data['ca'])) {
            throw new \InvalidArgumentException('basic-constraints: "ca" must be a boolean.');
        }

        if (isset($data['path_len_constraint']) && ! is_int($data['path_len_constraint'])) {
            throw new \InvalidArgumentException('basic-constraints: "path_len_constraint" must be an integer.');
        }

        if (isset($data['path_len_constraint']) && ! ($data['ca'] ?? false)) {
            throw new \InvalidArgumentException('basic-constraints: "path_len_constraint" requires "ca" to be true.');
        }

        if (isset($data['critical']) && ! is_bool($data['critical'])) {
            throw new \InvalidArgumentException('basic-constraints: "critical" must be a boolean.');
        }

        return new static(
            ca: $data['ca'] ?? false,
            pathLenConstraint: $data['path_len_constraint'] ?? null,
            critical: $data['critical'] ?? true,
        );
    }
}
