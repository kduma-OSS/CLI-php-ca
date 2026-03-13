<?php

namespace App\Commands\Key;

use App\Commands\Concerns\LoadsCaConfiguration;
use App\Commands\Concerns\LoadsPrivateKey;
use App\Storage\Entities\Key;
use App\Storage\Enums\KeyFile;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\error;

class ImportCommand extends Command
{
    use LoadsCaConfiguration;
    use LoadsPrivateKey;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'key:import {id} {pem?} {--ca= : Configuration file} {--password= : Password}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import an existing PEM private key into the CA database';

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

        $id = $this->argument('id');

        if ($config->database()->keys()->exists($id)) {
            stdErr(fn () => error("Key with id {$id} already exists"));
            return self::FAILURE;
        }

        $path = $this->argument('pem');

        if ($path) {
            if (!file_exists($path)) {
                stdErr(fn () => error("File not found: {$path}"));
                return self::FAILURE;
            }
            $pem = file_get_contents($path);
        } else {
            $pem = file_get_contents('php://stdin');
        }

        if (!$pem) {
            stdErr(fn () => error('No PEM data provided'));
            return self::FAILURE;
        }

        try {
            $private_key = $this->loadPrivateKey($pem, $password);
        } catch (\Exception $e) {
            stdErr(fn () => error('Failed to load private key: ' . $e->getMessage()));
            return self::FAILURE;
        }

        $public_key = $private_key->getPublicKey();
        $fingerprint = $public_key->getFingerprint();
        $key_size = $public_key->getLength();

        $entity = new Key(
            id: $id,
            size: $key_size,
            fingerprint: $fingerprint,
            createdAt: now()->toImmutable(),
        );

        $config->database()->keys()->save($entity);
        $config->database()->keys()->putFile($id, KeyFile::PrivateKey, $private_key->withPassword($password ?? false)->toString('PKCS8'));
        $config->database()->keys()->putFile($id, KeyFile::PublicKey, $public_key->toString('PKCS8'));

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
