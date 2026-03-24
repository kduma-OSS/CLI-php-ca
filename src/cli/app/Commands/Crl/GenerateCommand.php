<?php

namespace App\Commands\Crl;

use App\Commands\BaseCommand;
use DateTimeImmutable;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;

class GenerateCommand extends BaseCommand
{
    protected $signature = 'crl:generate {--ca-cert=} {--signer-cert=} {--next-update=+7days}';

    protected $description = 'Generate a CRL from current revocations';

    public function handle(): int
    {
        $ca = $this->getCertificationAuthority();

        $caCertId = $this->option('ca-cert') ?? $ca->state->getActiveCaCertificateId();
        if ($caCertId === null) {
            error('No active CA certificate. Specify --ca-cert or activate one.');

            return self::FAILURE;
        }

        $caCert = $ca->caCertificates->findOrNull($caCertId);
        if ($caCert === null) {
            error("CA certificate \"{$caCertId}\" not found.");

            return self::FAILURE;
        }

        $signerCertId = $this->option('signer-cert');
        if ($signerCertId !== null) {
            $signerCert = $ca->certificates->findOrNull($signerCertId);
            if ($signerCert === null) {
                error("Signer certificate \"{$signerCertId}\" not found.");

                return self::FAILURE;
            }
        }

        try {
            $nextUpdate = new DateTimeImmutable($this->option('next-update'));
        } catch (\Exception) {
            error('Invalid --next-update value.');

            return self::FAILURE;
        }

        $revocations = $ca->revocations->all();

        try {
            $builder = $ca->crls->getBuilder()
                ->caCertificate($caCertId)
                ->addRevocations($revocations)
                ->nextUpdate($nextUpdate);

            if ($signerCertId !== null) {
                $builder->signerCertificate($signerCertId);
            }

            $crl = $builder->save();
        } catch (\Throwable $e) {
            error($e->getMessage());

            return self::FAILURE;
        }

        stdErr(fn () => info("CRL generated: {$crl->id} ({$crl->crlNumber})"));
        $this->output->writeln($crl->id);

        return self::SUCCESS;
    }
}
