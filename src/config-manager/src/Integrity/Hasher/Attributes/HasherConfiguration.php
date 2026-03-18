<?php

declare(strict_types=1);

namespace KDuma\PhpCA\ConfigManager\Integrity\Hasher\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class HasherConfiguration
{
    public function __construct(
        public readonly string $type,
    ) {}
}
