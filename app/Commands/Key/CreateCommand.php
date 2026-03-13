<?php

namespace App\Commands\Key;

use App\Commands\Concerns\LoadsCaConfiguration;
use App\Storage\Entities\Key;
use App\Storage\Enums\KeyFile;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use phpseclib3\Crypt\RSA;

use function Laravel\Prompts\{error, password};

class CreateCommand extends Command
{
    use LoadsCaConfiguration;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'key:create {id} {--ca= : Configuration file} {--key-size=4096 : Key size} {--decrypted : Store private key without encryption} {--password= : Password}';

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
        try {
            $config = $this->getCaConfig();
        } catch (\RuntimeException $e) {
            error($e->getMessage());
            return self::FAILURE;
        }

        $id = $this->argument('id');

        if($config->database()->keys()->exists($id)) {
            error("Key with id {$id} already exists");
            return self::FAILURE;
        }

        $key_size = (int)$this->option('key-size');
        if ($this->option('decrypted')) {
            $password = false;
        } else {
            $password = $this->option('password') ?? false;

            if (! $password) {
                $password = password(label: 'Enter password for private key', required: true);
                if ($password !== password(label: 'Confirm password', required: true)) {
                    error('Passwords do not match');
                    return self::FAILURE;
                }
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
        $config->database()->keys()->putFile($id, KeyFile::PrivateKey, $private_key->withPassword($password)->toString('PKCS8'));
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
