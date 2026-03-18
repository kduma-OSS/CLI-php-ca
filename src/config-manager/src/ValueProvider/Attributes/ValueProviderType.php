<?php

declare(strict_types=1);

namespace KDuma\PhpCA\ConfigManager\ValueProvider\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class ValueProviderType
{
    public function __construct(
        public readonly string $type,
    ) {}
}
