<?php

namespace App\Commands\Crl;

use App\Commands\BaseCommand;
use phpseclib3\File\X509;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;

class GetDerCommand extends BaseCommand
{
    protected $signature = 'crl:get:der {id} {--stdout : Output raw DER to stdout} {--output= : Output file path}';
    protected $description = 'Output CRL in DER format';

    public function handle(): int
    {
        $ca = $this->getCertificationAuthority();
        $crl = $ca->crls->findOrNull($this->argument('id'));

        if ($crl === null) {
            error('CRL not found.');
            return self::FAILURE;
        }

        $x509 = new X509();
        $x509->loadCRL($crl->crl);
        $der = $x509->saveCRL($x509->getCurrentCert(), X509::FORMAT_DER);

        if ($this->option('stdout')) {
            $this->output->write($der);
            return self::SUCCESS;
        }

        $outputPath = $this->option('output') ?? $this->argument('id') . '.crl';

        file_put_contents($outputPath, $der);
        info("DER CRL written to {$outputPath}");

        return self::SUCCESS;
    }
}
