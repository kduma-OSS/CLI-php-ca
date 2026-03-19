<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\Extension\Resolver;

class InputResolver extends ExtensionValueResolver
{
    public function __construct(
        public readonly string $alias,
        public readonly string $label,
        public readonly ?string $default = null,
    ) {}

    public function resolve(IssuanceContext $context): string
    {
        return $context->inputProvider->ask($this->alias, $this->label, $this->default);
    }

    public static function type(): string
    {
        return 'input';
    }

    public static function fromArray(array $data): static
    {
        if (! isset($data['alias']) && ! isset($data['label'])) {
            throw new \InvalidArgumentException('input resolver: "alias" or "label" is required.');
        }

        $alias = $data['alias'] ?? $data['label'];

        if (! preg_match('/^[a-z0-9_-]+$/', $alias)) {
            throw new \InvalidArgumentException("input resolver: alias \"{$alias}\" must contain only lowercase letters, digits, hyphens, and underscores.");
        }

        return new static(
            alias: $alias,
            label: $data['label'] ?? $alias,
            default: $data['default'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'type' => self::type(),
            'alias' => $this->alias,
            'label' => $this->label,
            'default' => $this->default,
        ], fn ($v) => $v !== null);
    }
}
