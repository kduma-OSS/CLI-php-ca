<?php

namespace App\Commands\Authority\Certificate;

use App\Commands\Concerns\LoadsCaConfiguration;
use App\Storage\Entities\CaCertificateDetails;
use App\Storage\Entities\CaMetadata;
use App\Storage\Enums\CaFile;
use App\Support\CertificateFingerprint;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use phpseclib3\File\X509;

use function Laravel\Prompts\error;

class ImportCommand extends Command
{
    use LoadsCaConfiguration;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'authority:certificate:import {pem?} {--ca= : Configuration file} {--force : Force import, overwrite existing certificate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import a certificate into the authority';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $config = $this->getCaConfig();
        } catch (\RuntimeException $e) {
            stdErr(fn () => error($e->getMessage()));

            return self::FAILURE;
        }

        $ca = $config->database()->ca();

        if ($ca->metadata()?->certificate !== null && ! $this->option('force')) {
            stdErr(fn () => error('A certificate already exists. Use --force to overwrite.'));

            return self::FAILURE;
        }

        $path = $this->argument('pem');

        if ($path) {
            if (! file_exists($path)) {
                stdErr(fn () => error("File not found: {$path}"));

                return self::FAILURE;
            }
            $pem = file_get_contents($path);
        } else {
            $pem = file_get_contents('php://stdin');
        }

        if (! $pem) {
            stdErr(fn () => error('No PEM data provided'));

            return self::FAILURE;
        }

        $x509 = new X509;

        try {
            $cert = $x509->loadX509($pem);
        } catch (\Exception $e) {
            stdErr(fn () => error('Failed to load certificate: '.$e->getMessage()));

            return self::FAILURE;
        }

        if ($cert === false) {
            stdErr(fn () => error('Failed to parse certificate.'));

            return self::FAILURE;
        }

        $dn = $x509->getDN(X509::DN_STRING);
        $serialNumber = $x509->getCurrentCert()['tbsCertificate']['serialNumber']->toHex();
        $validFrom = new CarbonImmutable($x509->getCurrentCert()['tbsCertificate']['validity']['notBefore']['utcTime'] ?? $x509->getCurrentCert()['tbsCertificate']['validity']['notBefore']['generalTime']);
        $validTo = new CarbonImmutable($x509->getCurrentCert()['tbsCertificate']['validity']['notAfter']['utcTime'] ?? $x509->getCurrentCert()['tbsCertificate']['validity']['notAfter']['generalTime']);

        $basicConstraints = $x509->getExtension('id-ce-basicConstraints');
        $pathLengthConstraint = null;
        if (is_array($basicConstraints) && isset($basicConstraints['pathLenConstraint'])) {
            $pathLengthConstraint = (int) $basicConstraints['pathLenConstraint'];
        }

        $publicKey = $x509->getPublicKey();
        $fingerprint = $publicKey->getFingerprint();

        $key = $config->database()->keys()->forFingerprint($fingerprint);

        if ($key === null) {
            stdErr(fn () => error("No matching key found in database for fingerprint [{$fingerprint}]."));

            return self::FAILURE;
        }

        $ca->putFile(CaFile::Certificate, $pem);
        $ca->saveMetadata(new CaMetadata(
            key_id: $key->id,
            certificate: new CaCertificateDetails(
                serial_number: $serialNumber,
                distinguished_name: $dn,
                fingerprint: CertificateFingerprint::compute($pem),
                path_length_constraint: $pathLengthConstraint,
                valid_from: $validFrom,
                valid_to: $validTo,
            ),
        ));

        return self::SUCCESS;
    }

    /**
     * Define the command's schedule.
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
