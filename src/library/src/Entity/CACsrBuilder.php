<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Entity;

use KDuma\PhpCA\CertificationAuthority;
use KDuma\PhpCA\Helpers\KeyHelper;
use KDuma\PhpCA\Record\CertificateSubject\CertificateSubject;
use KDuma\PhpCA\Record\Extension\BaseExtension;
use phpseclib3\File\X509;

class CACsrBuilder
{
    private ?string $customId = null;

    private string|KeyEntity|null $key = null;

    private ?CertificateSubject $subject = null;

    /** @var BaseExtension[] */
    private array $extensions = [];

    public function __construct(
        private readonly CertificationAuthority $ca,
    ) {}

    public function id(string $id): static
    {
        $this->customId = $id;

        return $this;
    }

    public function key(string|KeyEntity $key): static
    {
        $this->key = $key;

        return $this;
    }

    public function subject(CertificateSubject $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    public function addExtension(BaseExtension $extension): static
    {
        $this->extensions[] = $extension;

        return $this;
    }

    public function save(): CACsrEntity
    {
        $keyEntity = $this->resolveKey();

        if ($this->subject === null) {
            throw new \LogicException('Subject is required.');
        }

        $privateKey = $keyEntity->getPrivateKey();
        if ($privateKey === null) {
            throw new \LogicException('CA CSR requires a private key.');
        }

        $x509 = new X509;
        $x509->setDN($this->subject->toString());
        $x509->setPrivateKey(KeyHelper::prepareForSigning($privateKey));

        $csr = $x509->signCSR();
        $csrPem = $x509->saveCSR($csr);

        $entity = new CACsrEntity;
        $entity->id = $this->customId;
        $entity->subject = $this->subject;
        $entity->keyId = $keyEntity->id;
        $entity->requestedExtensions = $this->extensions;
        $entity->fingerprint = hash('sha256', $csrPem);
        $entity->csr = $csrPem;

        $this->ca->caCsrs->save($entity);

        return $entity;
    }

    private function resolveKey(): KeyEntity
    {
        if ($this->key === null) {
            throw new \LogicException('Key is required.');
        }

        if ($this->key instanceof KeyEntity) {
            return $this->key;
        }

        return $this->ca->keys->find($this->key);
    }
}
