<?php

namespace App\Commands\Certificate\Issue;

use App\Commands\Concerns\LoadsCaConfiguration;
use App\Commands\Concerns\LoadsPrivateKey;
use App\Services\CertificateSigningService;
use App\Storage\Enums\KeyFile;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use phpseclib3\File\X509;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;

class CsrCommand extends Command
{
    use LoadsCaConfiguration;
    use LoadsPrivateKey;

    protected $signature = 'certificate:issue:csr {template} {pem?} {--ca= : Configuration file} {--password= : CA private key password} {--dn= : Override CSR\'s distinguished name} {--serial-number= : HEX serial number override} {--force : Skip DN confirmation}';

    protected $description = 'Issue a certificate from a CSR';

    public function handle(): int
    {
        try {
            $config = $this->getCaConfig();
        } catch (\RuntimeException $e) {
            error($e->getMessage());

            return self::FAILURE;
        }

        $caMetadata = $config->database()->ca()->metadata();
        if ($caMetadata?->key_id === null) {
            error('CA key is not configured.');

            return self::FAILURE;
        }

        $privateKeyPem = $config->database()->keys()->getFile($caMetadata->key_id, KeyFile::PrivateKey);
        if ($privateKeyPem === null) {
            error("CA private key [{$caMetadata->key_id}] not found.");

            return self::FAILURE;
        }

        try {
            $issuerKey = $this->loadPrivateKey($privateKeyPem);
        } catch (\Exception $e) {
            error('Failed to load CA private key: '.$e->getMessage());

            return self::FAILURE;
        }

        $path = $this->argument('pem');
        if ($path) {
            if (! file_exists($path)) {
                error("File not found: {$path}");

                return self::FAILURE;
            }
            $csrPem = file_get_contents($path);
        } else {
            $csrPem = file_get_contents('php://stdin');
        }

        if (! $csrPem) {
            error('No CSR PEM data provided.');

            return self::FAILURE;
        }

        $dnOverride = $this->option('dn');

        if ($dnOverride === null && ! $this->option('force')) {
            $x509 = new X509;
            $x509->loadCSR($csrPem);
            $csrDn = $x509->getDN(X509::DN_STRING);

            if (! confirm("Issue certificate with DN: {$csrDn}?")) {
                error('Aborted.');

                return self::FAILURE;
            }
        }

        $service = new CertificateSigningService($config->database(), $config);

        try {
            $service->issueFromCsr(
                templateName: $this->argument('template'),
                csrPem: $csrPem,
                issuerKey: $issuerKey,
                distinguishedNameOverride: $dnOverride,
                serialNumberOverride: $this->option('serial-number'),
            );
        } catch (\RuntimeException $e) {
            error($e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
