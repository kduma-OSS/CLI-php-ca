<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\Extension\Resolver;

abstract class ExtensionValueResolver
{
    abstract public function resolve(IssuanceContext $context): mixed;

    abstract public static function type(): string;

    abstract public static function fromArray(array $data): static;

    abstract public function toArray(): string|array;
}
