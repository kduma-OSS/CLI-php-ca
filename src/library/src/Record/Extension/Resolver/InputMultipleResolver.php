<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\Extension\Resolver;

class InputMultipleResolver extends ExtensionValueResolver
{
    public function __construct(
        public readonly string $alias,
        public readonly string $label,
    ) {}

    public function resolve(IssuanceContext $context): array
    {
        return $context->inputProvider->askMultiple($this->alias, $this->label);
    }

    public static function type(): string
    {
        return 'input-multiple';
    }

    public static function fromArray(array $data): static
    {
        if (! isset($data['alias']) && ! isset($data['label'])) {
            throw new \InvalidArgumentException('input-multiple resolver: "alias" or "label" is required.');
        }

        $alias = $data['alias'] ?? $data['label'];

        if (! preg_match('/^[a-z0-9_-]+$/', $alias)) {
            throw new \InvalidArgumentException("input-multiple resolver: alias \"{$alias}\" must contain only lowercase letters, digits, hyphens, and underscores.");
        }

        return new static(
            alias: $alias,
            label: $data['label'] ?? $alias,
        );
    }

    public function toArray(): array
    {
        return [
            'type' => self::type(),
            'alias' => $this->alias,
            'label' => $this->label,
        ];
    }
}
