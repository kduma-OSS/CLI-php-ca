<?php

namespace App\Storage\Repositories;

use App\Storage\Entities\CaMetadata;
use App\Storage\Infrastructure\SingletonRepository;

class CaRepository extends SingletonRepository
{
    protected function storageName(): string
    {
        return 'ca';
    }

    protected function entityClass(): string
    {
        return CaMetadata::class;
    }

    protected function allowedFiles(): array
    {
        return ['certificate.pem', 'key.pem', 'csr.pem'];
    }

    public function certificate(): ?string
    {
        return $this->getFile('certificate.pem');
    }

    public function saveCertificate(string $content): void
    {
        $this->putFile('certificate.pem', $content);
    }

    public function key(): ?string
    {
        return $this->getFile('key.pem');
    }

    public function saveKey(string $content): void
    {
        $this->putFile('key.pem', $content);
    }

    public function csr(): ?string
    {
        return $this->getFile('csr.pem');
    }

    public function saveCsr(string $content): void
    {
        $this->putFile('csr.pem', $content);
    }
}
