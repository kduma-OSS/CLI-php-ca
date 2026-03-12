<?php

namespace App\Storage;

use App\Storage\Repositories\CertificateRepository;
use App\Storage\Repositories\KeyRepository;
use Illuminate\Filesystem\Filesystem;

class Database
{
    private Filesystem $files;

    private ?KeyRepository $keyRepository = null;

    private ?CertificateRepository $certificateRepository = null;

    public function __construct(
        private string $path,
    ) {
        $this->files = new Filesystem;
    }

    public function keys(): KeyRepository
    {
        return $this->keyRepository ??= new KeyRepository($this->files, $this->path);
    }

    public function certificates(): CertificateRepository
    {
        return $this->certificateRepository ??= new CertificateRepository($this->files, $this->path);
    }

    public function caMetadata(): ?array
    {
        $path = $this->path.'/ca.json';

        if (! $this->files->exists($path)) {
            return null;
        }

        return json_decode($this->files->get($path), true);
    }

    public function saveCaMetadata(array $data): void
    {
        ksort($data);
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n";

        if (! $this->files->isDirectory($this->path)) {
            $this->files->makeDirectory($this->path, 0755, true);
        }

        $this->files->put($this->path.'/ca.json', $json);
    }

    public function caCertificate(): ?string
    {
        $path = $this->path.'/ca.crt.pem';

        if (! $this->files->exists($path)) {
            return null;
        }

        return $this->files->get($path);
    }

    public function saveCaCertificate(string $content): void
    {
        if (! $this->files->isDirectory($this->path)) {
            $this->files->makeDirectory($this->path, 0755, true);
        }

        $this->files->put($this->path.'/ca.crt.pem', $content);
    }

    public function caKey(): ?string
    {
        $path = $this->path.'/ca.key.pem';

        if (! $this->files->exists($path)) {
            return null;
        }

        return $this->files->get($path);
    }

    public function saveCaKey(string $content): void
    {
        if (! $this->files->isDirectory($this->path)) {
            $this->files->makeDirectory($this->path, 0755, true);
        }

        $this->files->put($this->path.'/ca.key.pem', $content);
    }

    public function caCsr(): ?string
    {
        $path = $this->path.'/ca.csr.pem';

        if (! $this->files->exists($path)) {
            return null;
        }

        return $this->files->get($path);
    }

    public function saveCaCsr(string $content): void
    {
        if (! $this->files->isDirectory($this->path)) {
            $this->files->makeDirectory($this->path, 0755, true);
        }

        $this->files->put($this->path.'/ca.csr.pem', $content);
    }
}
