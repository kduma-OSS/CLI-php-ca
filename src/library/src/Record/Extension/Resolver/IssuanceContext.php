<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\Extension\Resolver;

use KDuma\PhpCA\Entity\CACertificateEntity;
use KDuma\PhpCA\Entity\KeyEntity;
use KDuma\PhpCA\Record\CertificateSubject\CertificateSubject;
use KDuma\PhpCA\Record\CertificateValidity;

readonly class IssuanceContext
{
    public function __construct(
        public KeyEntity $subjectKey,
        public CACertificateEntity $caCertificate,
        public KeyEntity $caKey,
        public CertificateSubject $subject,
        public CertificateValidity $validity,
        public int $sequence,
        public string $serialNumber,
        public InputProviderInterface $inputProvider,
    ) {}

    public function getVariable(string $name): string
    {
        return match ($name) {
            'subject-cn' => $this->subject->getFirst('CN')?->value ?? '',
            'serial' => $this->serialNumber,
            'sequence' => (string) $this->sequence,
            'key-fingerprint' => $this->subjectKey->fingerprint,
            'ca-fingerprint' => $this->caCertificate->fingerprint,
            'ca-subject-cn' => $this->caCertificate->subject->getFirst('CN')?->value ?? '',
            'ca-key-fingerprint' => $this->caKey->fingerprint,
            'not-before' => $this->validity->notBefore->format('c'),
            'not-after' => $this->validity->notAfter->format('c'),
            default => throw new \InvalidArgumentException("Unknown template variable: {$name}"),
        };
    }
}
