<?php

namespace App\Commands\KeyManagement;

use App\Commands\Concerns\LoadsCaConfiguration;
use App\Storage\Entities\Key;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use phpseclib3\Crypt\RSA;

class ImportPrivateKeyCommand extends Command
{
    use LoadsCaConfiguration;

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
        $config = $this->getCaConfig();

        $id = $this->argument('id');

        if ($config->database()->keys()->exists($id)) {
            $this->error("Key with id {$id} already exists");
            return self::FAILURE;
        }

        $path = $this->argument('pem');

        if ($path) {
            if (!file_exists($path)) {
                $this->error("File not found: {$path}");
                return self::FAILURE;
            }
            $pem = file_get_contents($path);
        } else {
            $pem = file_get_contents('php://stdin');
        }

        if (!$pem) {
            $this->error('No PEM data provided');
            return self::FAILURE;
        }

        $password = $this->option('password') ?? false;

        try {
            $private_key = RSA::loadPrivateKey($pem, $password);
        } catch (\Exception $e) {
            if ($password !== false) {
                $this->error('Failed to load private key: ' . $e->getMessage());
                return self::FAILURE;
            }

            $password = $this->secret('Enter password for private key');
            if (!$password) {
                $this->error('Password cannot be empty');
                return self::FAILURE;
            }

            try {
                $private_key = RSA::loadPrivateKey($pem, $password);
            } catch (\Exception $e) {
                $this->error('Failed to load private key: ' . $e->getMessage());
                return self::FAILURE;
            }
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
        $config->database()->keys()->putFile($id, 'private.key', $private_key->withPassword($password)->toString('PKCS8'));
        $config->database()->keys()->putFile($id, 'public.key', $public_key->toString('PKCS8'));

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
