<?php

namespace App\Commands\KeyManagement;

use App\Commands\Concerns\LoadsCaConfiguration;
use App\Storage\Entities\Key;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use phpseclib3\Crypt\RSA;

class CreatePrivateKeyCommand extends Command
{
    use LoadsCaConfiguration;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'key:create {id} {--ca=:Configuration file} {--key-size=4096 : Key size} {--encrypted : Encrypt private key} {--password= : Password}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a new private key';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $config = $this->getCaConfig();

        $id = $this->argument('id');

        if($config->database()->keys()->exists($id)) {
            $this->error("Key with id {$id} already exists");
            return self::FAILURE;
        }

        $key_size = (int)$this->option('key-size');
        $password = $this->option('password') ?? false;

        if($this->option('encrypted') && !$password) {
            $password = $this->secret('Enter password for private key');
            if(!$password) {
                $this->error('Password cannot be empty');
                return self::FAILURE;
            }
            if($password !== $this->secret('Confirm password')) {
                $this->error('Passwords do not match');
                return self::FAILURE;
            }
        }

        $private_key = RSA::createKey($key_size);
        $public_key = $private_key->getPublicKey();
        $fingerprint = $public_key->getFingerprint();

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
