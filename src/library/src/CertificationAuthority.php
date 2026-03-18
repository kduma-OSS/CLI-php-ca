<?php

declare(strict_types=1);

namespace KDuma\PhpCA;

use KDuma\PhpCA\Entity\KeyEntityCollection;
use KDuma\PhpCA\Record\CertificateRecord;
use KDuma\PhpCA\Record\Enum\CertificateAttachment;
use KDuma\PhpCA\Record\Enum\KeyAttachment;
use KDuma\PhpCA\Record\KeyRecord;
use KDuma\SimpleDAL\Adapter\Contracts\StorageAdapterInterface;
use KDuma\SimpleDAL\Typed\Entity\TypedCollectionDefinition;
use KDuma\SimpleDAL\Typed\Store\TypedCollectionEntity;
use KDuma\SimpleDAL\Typed\TypedDataStore;

class CertificationAuthority
{
    private readonly TypedDataStore $store;
    private ?KeyEntityCollection $_keys = null;

    public function __construct(StorageAdapterInterface $adapter)
    {
        $this->store = new TypedDataStore($adapter, [
            new TypedCollectionDefinition(
                name: 'keys',
                recordClass: KeyRecord::class,
                attachmentEnum: KeyAttachment::class,
                indexedFields: ['fingerprint', 'has_private_key'],
            ),
            new TypedCollectionDefinition(
                name: 'certificates',
                recordClass: CertificateRecord::class,
                attachmentEnum: CertificateAttachment::class,
                indexedFields: ['serialNumber', 'keyId', 'sequence'],
            ),
        ]);
    }

    public KeyEntityCollection $keys {
        get => $this->_keys ??= new KeyEntityCollection($this->store->collection('keys'));
    }

    public function certificates(): TypedCollectionEntity
    {
        return $this->store->collection('certificates');
    }
}
