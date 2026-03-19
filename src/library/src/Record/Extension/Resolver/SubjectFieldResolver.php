<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\Extension\Resolver;

class SubjectFieldResolver extends ExtensionValueResolver
{
    public function __construct(
        public readonly string $field,
    ) {}

    public function resolve(IssuanceContext $context): string
    {
        return $context->subject->getFirst($this->field)?->value ?? '';
    }

    public static function type(): string
    {
        return 'subject-field';
    }

    public static function fromArray(array $data): static
    {
        return new static(
            field: $data['field'] ?? throw new \InvalidArgumentException('subject-field resolver requires "field".'),
        );
    }

    public function toArray(): array
    {
        return [
            'type' => self::type(),
            'field' => $this->field,
        ];
    }
}
