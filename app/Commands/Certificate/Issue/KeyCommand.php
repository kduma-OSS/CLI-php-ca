<?php

namespace App\Commands\Certificate\Issue;

use App\Commands\Concerns\LoadsCaConfiguration;
use App\Commands\Concerns\LoadsPrivateKey;
use App\Services\CertificateSigningService;
use App\Storage\Enums\KeyFile;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\error;

class KeyCommand extends Command
{
    use LoadsCaConfiguration;
    use LoadsPrivateKey;

    protected $signature = 'certificate:issue:key {template} {key_id} {distinguished_name} {--ca= : Configuration file} {--password= : CA private key password} {--serial-number= : HEX serial number override}';

    protected $description = 'Issue a certificate from a key ID';

    public function handle(): int
    {
        try {
            $config = $this->getCaConfig();
        } catch (\RuntimeException $e) {
            stdErr(fn () => error($e->getMessage()));

            return self::FAILURE;
        }

        $caMetadata = $config->database()->ca()->metadata();
        if ($caMetadata?->key_id === null) {
            stdErr(fn () => error('CA key is not configured.'));

            return self::FAILURE;
        }

        $privateKeyPem = $config->database()->keys()->getFile($caMetadata->key_id, KeyFile::PrivateKey);
        if ($privateKeyPem === null) {
            stdErr(fn () => error("CA private key [{$caMetadata->key_id}] not found."));

            return self::FAILURE;
        }

        try {
            $issuerKey = $this->loadPrivateKey($privateKeyPem);
        } catch (\Exception $e) {
            stdErr(fn () => error('Failed to load CA private key: '.$e->getMessage()));

            return self::FAILURE;
        }

        $service = new CertificateSigningService($config->database(), $config);

        try {
            $certificate = $service->issueFromKeyId(
                templateName: $this->argument('template'),
                keyId: $this->argument('key_id'),
                issuerKey: $issuerKey,
                distinguishedName: $this->argument('distinguished_name'),
                serialNumberOverride: $this->option('serial-number'),
            );
        } catch (\RuntimeException $e) {
            stdErr(fn () => error($e->getMessage()));

            return self::FAILURE;
        }

        $this->output->writeln($certificate->id);

        return self::SUCCESS;
    }

    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
