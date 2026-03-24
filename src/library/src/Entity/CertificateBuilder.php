<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Entity;

use DateTimeImmutable;
use KDuma\PhpCA\CertificationAuthority;
use KDuma\PhpCA\Helpers\FingerprintHelper;
use KDuma\PhpCA\Helpers\KeyHelper;
use KDuma\PhpCA\Helpers\X509ExtensionApplier;
use KDuma\PhpCA\Record\CertificateSubject\CertificateSubject;
use KDuma\PhpCA\Record\CertificateValidity;
use KDuma\PhpCA\Record\Enum\SignatureAlgorithm;
use KDuma\PhpCA\Record\Extension\BaseExtension;
use KDuma\PhpCA\Record\Extension\Resolver\InputProviderInterface;
use KDuma\PhpCA\Record\Extension\Resolver\IssuanceContext;
use KDuma\PhpCA\Record\Extension\Resolver\PresetInputProvider;
use phpseclib3\File\X509;

class CertificateBuilder
{
    private string|CertificateTemplateEntity|null $template = null;

    private string|KeyEntity|null $key = null;

    private ?CertificateSubject $subject = null;

    private string|CACertificateEntity|null $caCert = null;

    private string|KeyEntity|null $caKey = null;

    private string|CsrEntity|null $csr = null;

    private bool $useSubjectFromCsr = true;

    private bool $useExtensionsFromCsr = true;

    /** @var BaseExtension[] */
    private array $additionalExtensions = [];

    private ?InputProviderInterface $inputProvider = null;

    public function __construct(
        private readonly CertificationAuthority $ca,
    ) {}

    public function template(string|CertificateTemplateEntity $template): static
    {
        $this->template = $template;

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

    public function signedBy(string|CACertificateEntity $caCert, string|KeyEntity $caKey): static
    {
        $this->caCert = $caCert;
        $this->caKey = $caKey;

        return $this;
    }

    public function fromCsr(string|CsrEntity $csr, bool $useSubject = true, bool $useExtensions = true): static
    {
        $this->csr = $csr;
        $this->useSubjectFromCsr = $useSubject;
        $this->useExtensionsFromCsr = $useExtensions;

        return $this;
    }

    public function addExtension(BaseExtension $extension): static
    {
        $this->additionalExtensions[] = $extension;

        return $this;
    }

    public function inputProvider(InputProviderInterface $inputProvider): static
    {
        $this->inputProvider = $inputProvider;

        return $this;
    }

    public function save(): CertificateEntity
    {
        $templateEntity = $this->resolveTemplate();
        $caCertEntity = $this->resolveCaCert();
        $caKeyEntity = $this->resolveCaKey();
        $csrEntity = $this->resolveCsr();

        $caPrivateKey = $caKeyEntity->getPrivateKey();
        if ($caPrivateKey === null) {
            throw new \LogicException('CA key must have a private key for signing.');
        }

        // Determine subject
        $subject = $this->subject;
        if ($subject === null && $csrEntity !== null && $this->useSubjectFromCsr) {
            $subject = $csrEntity->subject;
        }
        if ($subject === null) {
            throw new \LogicException('Subject is required.');
        }

        // Determine key
        $keyEntity = $this->resolveSubjectKey($csrEntity);
        $subjectPublicKey = $keyEntity->getPublicKey();

        // Build certificate
        $sequence = $this->ca->state->nextSequence();

        $notBefore = new DateTimeImmutable;
        $effectiveValidity = $templateEntity->getEffectiveValidity($this->ca->templates);
        if ($effectiveValidity === null) {
            throw new \LogicException('Template has no validity (and no parent with validity).');
        }
        $notAfter = $notBefore->add($effectiveValidity);
        $validity = new CertificateValidity($notBefore, $notAfter);

        // Resolve template extensions (with inheritance) via IssuanceContext
        $effectiveExtensionTemplates = $templateEntity->getEffectiveExtensions($this->ca->templates);
        $context = new IssuanceContext(
            subjectKey: $keyEntity,
            caCertificate: $caCertEntity,
            caKey: $caKeyEntity,
            subject: $subject,
            validity: $validity,
            sequence: $sequence,
            serialNumber: '', // will be set after signing
            inputProvider: $this->inputProvider ?? new PresetInputProvider,
        );

        $extensions = array_map(
            fn ($tpl) => $tpl->resolve($context),
            $effectiveExtensionTemplates,
        );

        if ($csrEntity !== null && $this->useExtensionsFromCsr) {
            $extensions = array_merge($extensions, $csrEntity->requestedExtensions);
        }
        $extensions = array_merge($extensions, $this->additionalExtensions);

        $issuerX509 = new X509;
        $issuerX509->loadX509($caCertEntity->certificate);
        $issuerX509->setPrivateKey(KeyHelper::prepareForSigning($caPrivateKey));

        $subjectX509 = new X509;
        $subjectX509->setDN($subject->toString());
        $subjectX509->setPublicKey(KeyHelper::preparePublicKey($subjectPublicKey));

        $x509 = new X509;
        $x509->setStartDate($notBefore);
        $x509->setEndDate($notAfter);

        // Apply extensions from template + CSR + additional
        X509ExtensionApplier::apply($x509, $extensions);
        // Auto-add SKI
        X509ExtensionApplier::setSubjectKeyIdentifier($x509, $subjectPublicKey);

        $result = $x509->sign($issuerX509, $subjectX509);
        $certPem = $x509->saveX509($result);

        // Parse back the signed cert for fields
        $parsedX509 = new X509;
        $parsedX509->loadX509($certPem);
        $cert = $parsedX509->getCurrentCert();
        $tbs = $cert['tbsCertificate'];

        $serialNumber = $tbs['serialNumber']->toHex();

        $entity = new CertificateEntity;
        $entity->id = "{$sequence}-{$serialNumber}";
        $entity->version = 3;
        $entity->serialNumber = $serialNumber;
        $entity->signatureAlgorithm = SignatureAlgorithm::fromAsn1(
            $cert['signatureAlgorithm']['algorithm']
        );
        $entity->issuer = CertificateSubject::fromString($parsedX509->getIssuerDN(X509::DN_STRING));
        $entity->subject = $subject;
        $entity->validity = $validity;

        $ski = $parsedX509->getExtension('id-ce-subjectKeyIdentifier');
        $entity->subjectKeyIdentifier = is_string($ski) ? bin2hex($ski) : FingerprintHelper::compute($subjectPublicKey);

        $aki = $parsedX509->getExtension('id-ce-authorityKeyIdentifier');
        $entity->authorityKeyIdentifier = is_array($aki) && isset($aki['keyIdentifier'])
            ? bin2hex($aki['keyIdentifier'])
            : $caCertEntity->subjectKeyIdentifier;

        $entity->extensions = $extensions;
        $entity->keyId = $keyEntity->id;
        $entity->caCertificateId = $caCertEntity->id;
        $entity->sequence = $sequence;
        $entity->fingerprint = FingerprintHelper::computeCertificateFingerprint($certPem);
        $entity->templateId = $templateEntity->id;
        $entity->certificate = $certPem;

        $this->ca->certificates->save($entity);

        // Update CSR with issued certificate ID
        if ($csrEntity !== null) {
            $csrEntity->certificateId = $entity->id;
            $this->ca->csrs->save($csrEntity);
        }

        return $entity;
    }

    private function resolveTemplate(): CertificateTemplateEntity
    {
        if ($this->template === null) {
            throw new \LogicException('Template is required.');
        }

        if ($this->template instanceof CertificateTemplateEntity) {
            return $this->template;
        }

        return $this->ca->templates->find($this->template);
    }

    private function resolveCaCert(): CACertificateEntity
    {
        if ($this->caCert === null) {
            throw new \LogicException('CA certificate is required (use signedBy()).');
        }

        if ($this->caCert instanceof CACertificateEntity) {
            return $this->caCert;
        }

        return $this->ca->caCertificates->find($this->caCert);
    }

    private function resolveCaKey(): KeyEntity
    {
        if ($this->caKey === null) {
            throw new \LogicException('CA key is required (use signedBy()).');
        }

        if ($this->caKey instanceof KeyEntity) {
            return $this->caKey;
        }

        return $this->ca->keys->find($this->caKey);
    }

    private function resolveCsr(): ?CsrEntity
    {
        if ($this->csr === null) {
            return null;
        }

        if ($this->csr instanceof CsrEntity) {
            return $this->csr;
        }

        return $this->ca->csrs->find($this->csr);
    }

    private function resolveSubjectKey(?CsrEntity $csrEntity): KeyEntity
    {
        if ($this->key !== null) {
            if ($this->key instanceof KeyEntity) {
                return $this->key;
            }

            return $this->ca->keys->find($this->key);
        }

        if ($csrEntity !== null) {
            return $this->ca->keys->find($csrEntity->keyId);
        }

        throw new \LogicException('Key is required (use key() or fromCsr()).');
    }
}
