<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\Extension\Resolver;

class TemplateStringResolver extends ExtensionValueResolver
{
    public function __construct(
        public readonly string $template,
    ) {}

    public function resolve(IssuanceContext $context): string
    {
        return preg_replace_callback('/\{([a-z0-9-]+)\}/', function ($matches) use ($context) {
            return $context->getVariable($matches[1]);
        }, $this->template);
    }

    public static function type(): string
    {
        return 'template';
    }

    public static function fromArray(array $data): static
    {
        return new static(
            template: $data['template'] ?? throw new \InvalidArgumentException('template resolver requires "template".'),
        );
    }

    public function toArray(): array
    {
        return [
            'type' => self::type(),
            'template' => $this->template,
        ];
    }
}
