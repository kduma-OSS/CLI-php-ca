<?php

namespace App\Commands\Crl;

use App\Commands\BaseCommand;

use function Laravel\Prompts\info;

class ListCommand extends BaseCommand
{
    protected $signature = 'crl:list';
    protected $description = 'List all CRLs';

    public function handle(): int
    {
        $ca = $this->getCertificationAuthority();
        $crls = $ca->crls->all();

        if (empty($crls)) {
            info('No CRLs found.');
            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'CRL #', 'CA Cert', 'Signer Cert', 'This Update', 'Next Update', 'Expired'],
            array_map(fn ($c) => [
                $c->id,
                $c->crlNumber,
                $c->caCertificateId ?? '-',
                $c->signerCertificateId ?? '-',
                $c->thisUpdate->format('Y-m-d H:i:s'),
                $c->nextUpdate?->format('Y-m-d H:i:s') ?? '-',
                $c->isExpired() ? 'Yes' : 'No',
            ], $crls),
        );

        return self::SUCCESS;
    }
}
