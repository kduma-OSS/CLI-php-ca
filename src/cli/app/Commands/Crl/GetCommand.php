<?php

namespace App\Commands\Crl;

use App\Commands\BaseCommand;

use function Laravel\Prompts\error;

class GetCommand extends BaseCommand
{
    protected $signature = 'crl:get {id}';
    protected $description = 'Output CRL PEM';

    public function handle(): int
    {
        $ca = $this->getCertificationAuthority();
        $crl = $ca->crls->findOrNull($this->argument('id'));

        if ($crl === null) {
            error('CRL not found.');
            return self::FAILURE;
        }

        $this->output->writeln($crl->crl);

        if ($crl->signerCertificateId !== null) {
            $signerCert = $ca->certificates->findOrNull($crl->signerCertificateId);
            if ($signerCert !== null) {
                $this->output->writeln($signerCert->certificate);
            }
        }

        return self::SUCCESS;
    }
}
