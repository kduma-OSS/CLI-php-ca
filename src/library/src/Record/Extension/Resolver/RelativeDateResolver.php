<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\Extension\Resolver;

use DateInterval;
use DateTimeImmutable;

class RelativeDateResolver extends ExtensionValueResolver
{
    public function __construct(
        public readonly string $base,
        public readonly ?string $offset = null,
    ) {}

    public function resolve(IssuanceContext $context): DateTimeImmutable
    {
        $date = match ($this->base) {
            'not-before', 'notBefore' => $context->validity->notBefore,
            'not-after', 'notAfter' => $context->validity->notAfter,
            'now' => new DateTimeImmutable,
            default => throw new \InvalidArgumentException("Unknown date base: {$this->base}"),
        };

        if ($this->offset !== null) {
            $isNegative = str_starts_with($this->offset, '-');
            $intervalStr = ltrim($this->offset, '-+');
            $interval = new DateInterval($intervalStr);

            if ($isNegative) {
                $date = $date->sub($interval);
            } else {
                $date = $date->add($interval);
            }
        }

        return $date;
    }

    public static function type(): string
    {
        return 'relative-date';
    }

    public static function fromArray(array $data): static
    {
        return new static(
            base: $data['base'] ?? 'not-before',
            offset: $data['offset'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'type' => self::type(),
            'base' => $this->base,
            'offset' => $this->offset,
        ], fn ($v) => $v !== null);
    }
}
