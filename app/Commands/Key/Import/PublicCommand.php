<?php

namespace App\Commands\Key\Import;

use App\Commands\Concerns\LoadsCaConfiguration;
use App\Storage\Entities\Key;
use App\Storage\Enums\KeyFile;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use phpseclib3\Crypt\RSA;

use function Laravel\Prompts\error;

class PublicCommand extends Command
{
    use LoadsCaConfiguration;

    protected $signature = 'key:import:public {id} {pem?} {--ca= : Configuration file}';

    protected $description = 'Import a public key PEM into the CA database';

    public function handle(): int
    {
        try {
            $config = $this->getCaConfig();
        } catch (\RuntimeException $e) {
            stdErr(fn () => error($e->getMessage()));

            return self::FAILURE;
        }

        $id = $this->argument('id');

        if ($config->database()->keys()->exists($id)) {
            stdErr(fn () => error("Key with id {$id} already exists"));

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

        try {
            $publicKey = RSA::loadPublicKey($pem);
        } catch (\Exception $e) {
            stdErr(fn () => error('Failed to load public key: '.$e->getMessage()));

            return self::FAILURE;
        }

        if (! $publicKey instanceof RSA\PublicKey) {
            stdErr(fn () => error('Only RSA public keys are supported.'));

            return self::FAILURE;
        }

        $fingerprint = $publicKey->getFingerprint();
        $keySize = $publicKey->getLength();

        $entity = new Key(
            id: $id,
            size: $keySize,
            fingerprint: $fingerprint,
            createdAt: now()->toImmutable(),
            private: false,
        );

        $config->database()->keys()->save($entity);
        $config->database()->keys()->putFile($id, KeyFile::PublicKey, $publicKey->toString('PKCS8'));

        return self::SUCCESS;
    }

    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
