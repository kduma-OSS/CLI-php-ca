<?php

declare(strict_types=1);

namespace KDuma\PhpCA\ConfigManager\Adapter\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class AdapterConfiguration
{
    public function __construct(
        public readonly string $type,
    ) {}
}
