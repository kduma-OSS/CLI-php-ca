<?php

namespace App\Commands\Ca;

use App\Commands\BaseCommand;
use DateInterval;
use KDuma\PhpCA\Record\CertificateSubject\CertificateSubject;
use KDuma\PhpCA\Record\Extension\Extensions\BasicConstraintsExtension;
use KDuma\PhpCA\Record\Extension\Extensions\KeyUsageExtension;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\text;

class SelfSignCommand extends BaseCommand
{
    protected $signature = 'ca:self-sign {--id=} {--key=} {--dn=} {--validity=P20Y} {--activate}';

    protected $description = 'Create a self-signed root CA certificate';

    public function handle(): int
    {
        $ca = $this->getCertificationAuthority();

        $keyId = $this->option('key') ?? text('Key ID', required: true);
        $dn = $this->option('dn') ?? text('Distinguished Name (e.g. CN=My CA, O=Org, C=US)', required: true);

        try {
            $validity = new DateInterval($this->option('validity'));
        } catch (\Exception) {
            error('Invalid validity interval.');

            return self::FAILURE;
        }

        try {
            $cert = $ca->caCertificates->getBuilder()
                ->id($this->option('id'))
                ->selfSigned()
                ->key($keyId)
                ->subject(CertificateSubject::fromString($dn))
                ->validity($validity)
                ->addExtension(new BasicConstraintsExtension(ca: true))
                ->addExtension(new KeyUsageExtension(keyCertSign: true, cRLSign: true))
                ->save();
        } catch (\Throwable $e) {
            error($e->getMessage());

            return self::FAILURE;
        }

        info("CA certificate created: {$cert->id}");

        if ($this->option('activate')) {
            $ca->state->setActiveCaCertificateId($cert->id);
            info('Set as active CA certificate.');
        }

        return self::SUCCESS;
    }
}
