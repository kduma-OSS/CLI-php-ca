<?php

declare(strict_types=1);

namespace KDuma\PhpCA;

use KDuma\PhpCA\Entity\CACertificateEntityCollection;
use KDuma\PhpCA\Entity\CACsrEntityCollection;
use KDuma\PhpCA\Entity\CaStateEntity;
use KDuma\PhpCA\Entity\CertificateEntityCollection;
use KDuma\PhpCA\Entity\CertificateTemplateEntityCollection;
use KDuma\PhpCA\Entity\CrlEntityCollection;
use KDuma\PhpCA\Entity\CsrEntityCollection;
use KDuma\PhpCA\Entity\KeyEntityCollection;
use KDuma\PhpCA\Entity\RevocationEntityCollection;
use KDuma\PhpCA\Record\CACertificateRecord;
use KDuma\PhpCA\Record\CACsrRecord;
use KDuma\PhpCA\Record\CaStateRecord;
use KDuma\PhpCA\Record\CertificateRecord;
use KDuma\PhpCA\Record\CertificateTemplateRecord;
use KDuma\PhpCA\Record\CrlRecord;
use KDuma\PhpCA\Record\CsrRecord;
use KDuma\PhpCA\Record\Enum\CACertificateAttachment;
use KDuma\PhpCA\Record\Enum\CACsrAttachment;
use KDuma\PhpCA\Record\Enum\CertificateAttachment;
use KDuma\PhpCA\Record\Enum\CrlAttachment;
use KDuma\PhpCA\Record\Enum\CsrAttachment;
use KDuma\PhpCA\Record\Enum\KeyAttachment;
use KDuma\PhpCA\Record\KeyRecord;
use KDuma\PhpCA\Record\RevocationRecord;
use KDuma\SimpleDAL\Adapter\Contracts\StorageAdapterInterface;
use KDuma\SimpleDAL\Typed\Entity\TypedCollectionDefinition;
use KDuma\SimpleDAL\Typed\Entity\TypedSingletonDefinition;
use KDuma\SimpleDAL\Typed\TypedDataStore;

class CertificationAuthority
{
    private readonly TypedDataStore $store;

    private ?KeyEntityCollection $_keys = null;
    private ?CertificateTemplateEntityCollection $_templates = null;
    private ?CertificateEntityCollection $_certificates = null;
    private ?CsrEntityCollection $_csrs = null;
    private ?CACsrEntityCollection $_caCsrs = null;
    private ?CACertificateEntityCollection $_caCertificates = null;
    private ?RevocationEntityCollection $_revocations = null;
    private ?CrlEntityCollection $_crls = null;
    private ?CaStateEntity $_state = null;

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
                name: 'templates',
                recordClass: CertificateTemplateRecord::class,
                indexedFields: ['parent_id'],
            ),
            new TypedCollectionDefinition(
                name: 'certificates',
                recordClass: CertificateRecord::class,
                attachmentEnum: CertificateAttachment::class,
                indexedFields: ['serial_number', 'key_id', 'sequence', 'ca_certificate_id'],
            ),
            new TypedCollectionDefinition(
                name: 'certificate_signing_requests',
                recordClass: CsrRecord::class,
                attachmentEnum: CsrAttachment::class,
                indexedFields: ['key_id', 'certificate_id'],
            ),
            new TypedCollectionDefinition(
                name: 'ca_certificate_signing_requests',
                recordClass: CACsrRecord::class,
                attachmentEnum: CACsrAttachment::class,
                indexedFields: ['key_id'],
            ),
            new TypedCollectionDefinition(
                name: 'ca_certificates',
                recordClass: CACertificateRecord::class,
                attachmentEnum: CACertificateAttachment::class,
                indexedFields: ['key_id', 'is_self_signed'],
            ),
            new TypedCollectionDefinition(
                name: 'revocations',
                recordClass: RevocationRecord::class,
                indexedFields: ['certificate_id', 'serial_number', 'ca_certificate_id'],
            ),
            new TypedCollectionDefinition(
                name: 'certificate_revocation_lists',
                recordClass: CrlRecord::class,
                attachmentEnum: CrlAttachment::class,
                indexedFields: ['ca_certificate_id', 'crl_number'],
            ),
            new TypedSingletonDefinition(
                name: 'ca_state',
                recordClass: CaStateRecord::class,
            ),
        ]);
    }

    public KeyEntityCollection $keys {
        get => $this->_keys ??= new KeyEntityCollection($this->store->collection('keys'));
    }

    public CertificateTemplateEntityCollection $templates {
        get => $this->_templates ??= new CertificateTemplateEntityCollection($this->store->collection('templates'), $this);
    }

    public CertificateEntityCollection $certificates {
        get => $this->_certificates ??= new CertificateEntityCollection($this->store->collection('certificates'), $this);
    }

    public CsrEntityCollection $csrs {
        get => $this->_csrs ??= new CsrEntityCollection($this->store->collection('certificate_signing_requests'));
    }

    public CACsrEntityCollection $caCsrs {
        get => $this->_caCsrs ??= new CACsrEntityCollection($this->store->collection('ca_certificate_signing_requests'), $this);
    }

    public CACertificateEntityCollection $caCertificates {
        get => $this->_caCertificates ??= new CACertificateEntityCollection($this->store->collection('ca_certificates'), $this);
    }

    public RevocationEntityCollection $revocations {
        get => $this->_revocations ??= new RevocationEntityCollection($this->store->collection('revocations'));
    }

    public CrlEntityCollection $crls {
        get => $this->_crls ??= new CrlEntityCollection($this->store->collection('certificate_revocation_lists'), $this);
    }

    public CaStateEntity $state {
        get => $this->_state ??= new CaStateEntity($this->store->singleton('ca_state'));
    }
}
