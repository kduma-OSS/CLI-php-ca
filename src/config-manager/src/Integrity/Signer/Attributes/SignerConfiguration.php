<?php

declare(strict_types=1);

namespace KDuma\PhpCA\ConfigManager\Integrity\Signer\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class SignerConfiguration
{
    public function __construct(
        public readonly string $type,
    ) {}
}
