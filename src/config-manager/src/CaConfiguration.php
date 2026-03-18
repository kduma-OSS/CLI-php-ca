<?php

declare(strict_types=1);

namespace KDuma\PhpCA\ConfigManager;

use KDuma\PhpCA\CertificationAuthority;
use KDuma\PhpCA\ConfigManager\Adapter\BaseAdapterConfiguration;
use KDuma\PhpCA\ConfigManager\Encryption\EncryptionConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\IntegrityConfiguration;
use KDuma\SimpleDAL\Encryption\EncryptingStorageAdapter;
use KDuma\SimpleDAL\Integrity\IntegrityStorageAdapter;

readonly class CaConfiguration
{
    public function __construct(
        public BaseAdapterConfiguration $adapter,
        public ?IntegrityConfiguration $integrity = null,
        public ?EncryptionConfiguration $encryption = null,
    ) {}

    public function createCertificationAuthority(): CertificationAuthority
    {
        $adapter = $this->adapter->createAdapter();

        if ($this->integrity !== null) {
            $adapter = new IntegrityStorageAdapter($adapter, $this->integrity->createIntegrityConfig());
        }

        if ($this->encryption !== null) {
            $adapter = new EncryptingStorageAdapter($adapter, $this->encryption->createEncryptionConfig());
        }

        return new CertificationAuthority($adapter);
    }
}
