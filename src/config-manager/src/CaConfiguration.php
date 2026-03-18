<?php

declare(strict_types=1);

namespace KDuma\PhpCA\ConfigManager;

use KDuma\PhpCA\ConfigManager\Adapter\BaseAdapterConfiguration;
use KDuma\PhpCA\ConfigManager\Encryption\EncryptionConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\IntegrityConfiguration;

readonly class CaConfiguration
{
    public function __construct(
        public BaseAdapterConfiguration $adapter,
        public ?IntegrityConfiguration $integrity = null,
        public ?EncryptionConfiguration $encryption = null,
    ) {}
}
