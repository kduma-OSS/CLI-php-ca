<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\Extension\Extensions;

use KDuma\PhpCA\Record\Extension\BaseExtension;

readonly class NetscapeCommentExtension extends BaseExtension
{
    public function __construct(
        public string $comment,
        private bool $critical = false,
    ) {}

    public static function oid(): string
    {
        return '2.16.840.1.113730.1.13';
    }

    public static function name(): string
    {
        return 'netscape-comment';
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
            'comment' => $this->comment,
        ];
    }

    public static function fromArray(array $data): static
    {
        return new static(
            comment: $data['comment'],
            critical: $data['critical'] ?? false,
        );
    }
}
