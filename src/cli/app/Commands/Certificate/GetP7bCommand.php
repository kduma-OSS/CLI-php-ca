<?php

namespace App\Commands\Certificate;

use App\Commands\BaseCommand;
use KDuma\PhpCA\Helpers\Pkcs7Builder;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;

class GetP7bCommand extends BaseCommand
{
    protected $signature = 'certificate:get:p7b {id} {--stdout : Output raw DER to stdout} {--output= : Output file path} {--no-chain : Only the certificate, skip CA chain}';

    protected $description = 'Output certificate as PKCS#7 (.p7b) bundle';

    public function handle(): int
    {
        $ca = $this->getCertificationAuthority();
        $cert = $ca->certificates->findOrNull($this->argument('id'));

        if ($cert === null) {
            error('Certificate not found.');

            return self::FAILURE;
        }

        $certs = [$cert->certificate];

        if (! $this->option('no-chain')) {
            $caCert = $ca->caCertificates->findOrNull($cert->caCertificateId);
            if ($caCert) {
                $certs[] = $caCert->certificate;
                if ($caCert->chain) {
                    $certs[] = $caCert->chain;
                }
            }
        }

        $p7b = Pkcs7Builder::buildCertificateBundle($certs);

        if ($this->option('stdout')) {
            $this->output->write($p7b);

            return self::SUCCESS;
        }

        $outputPath = $this->option('output') ?? $this->argument('id').'.p7b';

        file_put_contents($outputPath, $p7b);
        info("PKCS#7 bundle written to {$outputPath}");

        return self::SUCCESS;
    }
}
