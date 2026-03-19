<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\Extension\Template\Templates;

use KDuma\PhpCA\Record\Extension\BaseExtension;
use KDuma\PhpCA\Record\Extension\Extensions\NetscapeCommentExtension;
use KDuma\PhpCA\Record\Extension\Resolver\ExtensionValueResolver;
use KDuma\PhpCA\Record\Extension\Resolver\ExtensionValueResolverFactory;
use KDuma\PhpCA\Record\Extension\Resolver\IssuanceContext;
use KDuma\PhpCA\Record\Extension\Resolver\LiteralResolver;
use KDuma\PhpCA\Record\Extension\Template\BaseExtensionTemplate;

class NetscapeCommentExtensionTemplate extends BaseExtensionTemplate
{
    public function __construct(
        public readonly ExtensionValueResolver $comment,
        public readonly bool $critical = false,
    ) {}

    public static function name(): string
    {
        return 'netscape-comment';
    }

    public function resolve(IssuanceContext $context): BaseExtension
    {
        return new NetscapeCommentExtension(
            comment: $this->comment->resolve($context),
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
            'comment' => $this->comment->toArray(),
        ];
    }

    public static function fromArray(array $data): static
    {
        if (isset($data['critical']) && ! is_bool($data['critical'])) {
            throw new \InvalidArgumentException('netscape-comment: "critical" must be a boolean.');
        }

        $comment = isset($data['comment'])
            ? ExtensionValueResolverFactory::fromMixed($data['comment'])
            : new LiteralResolver('');

        if (! $comment instanceof ExtensionValueResolver) {
            throw new \InvalidArgumentException('netscape-comment: "comment" must be a string or resolver object.');
        }

        return new static(
            comment: $comment,
            critical: $data['critical'] ?? false,
        );
    }
}
