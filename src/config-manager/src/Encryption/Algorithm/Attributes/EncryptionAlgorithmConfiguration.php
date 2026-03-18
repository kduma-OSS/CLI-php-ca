<?php

declare(strict_types=1);

namespace KDuma\PhpCA\ConfigManager\Encryption\Algorithm\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class EncryptionAlgorithmConfiguration
{
    public function __construct(
        public readonly string $type,
    ) {}
}
