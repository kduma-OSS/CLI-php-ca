<?php

namespace App\Commands\Ca\Csr;

use App\Commands\BaseCommand;
use KDuma\PhpCA\Record\CertificateSubject\CertificateSubject;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\text;

class CreateCommand extends BaseCommand
{
    protected $signature = 'ca:csr:create {--id=} {--key=} {--dn=}';
    protected $description = 'Create a CSR for the CA';

    public function handle(): int
    {
        $ca = $this->getCertificationAuthority();

        $keyId = $this->option('key') ?? text('Key ID', required: true);
        $dn = $this->option('dn') ?? text('Distinguished Name', required: true);

        try {
            $builder = $ca->caCsrs->getBuilder()
                ->key($keyId)
                ->subject(CertificateSubject::fromString($dn));

            if ($this->option('id')) {
                $builder->id($this->option('id'));
            }

            $csr = $builder->save();
        } catch (\Throwable $e) {
            error($e->getMessage());
            return self::FAILURE;
        }

        info("CA CSR created: {$csr->id}");

        return self::SUCCESS;
    }
}
