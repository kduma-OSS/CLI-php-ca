<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\Extension;

abstract readonly class BaseExtension
{
    abstract public static function oid(): string;

    abstract public static function name(): string;

    abstract public function isCritical(): bool;

    abstract public function toArray(): array;

    abstract public static function fromArray(array $data): static;
}
