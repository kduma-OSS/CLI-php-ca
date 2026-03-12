<?php

namespace App\Storage;

use App\Storage\Repositories\CaRepository;
use App\Storage\Repositories\CertificateRepository;
use App\Storage\Repositories\KeyRepository;
use Illuminate\Filesystem\Filesystem;

class Database
{
    private Filesystem $files;

    private ?KeyRepository $keyRepository = null;

    private ?CertificateRepository $certificateRepository = null;

    private ?CaRepository $caRepository = null;

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

    public function ca(): CaRepository
    {
        return $this->caRepository ??= new CaRepository($this->files, $this->path);
    }
}
