<?php

namespace App\Commands\Ca;

use App\Commands\BaseCommand;
use phpseclib3\File\X509;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;

class GetDerCommand extends BaseCommand
{
    protected $signature = 'ca:get:der {id} {--stdout : Output raw DER to stdout} {--output= : Output file path}';

    protected $description = 'Output CA certificate in DER format';

    public function handle(): int
    {
        $ca = $this->getCertificationAuthority();
        $cert = $ca->caCertificates->findOrNull($this->argument('id'));

        if ($cert === null) {
            error('CA certificate not found.');

            return self::FAILURE;
        }

        $x509 = new X509;
        $x509->loadX509($cert->certificate);
        $der = $x509->saveX509($x509->getCurrentCert(), X509::FORMAT_DER);

        if ($this->option('stdout')) {
            $this->output->write($der);

            return self::SUCCESS;
        }

        $outputPath = $this->option('output') ?? $this->argument('id').'.der';

        file_put_contents($outputPath, $der);
        info("DER certificate written to {$outputPath}");

        return self::SUCCESS;
    }
}
