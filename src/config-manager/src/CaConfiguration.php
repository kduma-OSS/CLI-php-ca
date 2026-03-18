<?php

declare(strict_types=1);

namespace KDuma\PhpCA\ConfigManager;

use KDuma\PhpCA\ConfigManager\Adapter\BaseAdapterConfiguration;

readonly class CaConfiguration
{
    public function __construct(
        public BaseAdapterConfiguration $adapter,
    ) {}
}
