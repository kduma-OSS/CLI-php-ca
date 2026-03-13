<?php

namespace App\Commands\Key\Password;

use App\Commands\Concerns\LoadsCaConfiguration;
use App\Commands\Concerns\LoadsPrivateKey;
use App\Storage\Enums\KeyFile;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class UpdateCommand extends Command
{
    use LoadsCaConfiguration;
    use LoadsPrivateKey;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'key:password:update {id} {--ca= : Configuration file} {--password= : Old Password} {--decrypted : Store private key without encryption} {--new-password= : New Password}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the password on a private key';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $config = $this->getCaConfig();
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $id = $this->argument('id');

        if (! $config->database()->keys()->exists($id)) {
            $this->error("Key [{$id}] does not exist.");
            return self::FAILURE;
        }

        $pem = $config->database()->keys()->getFile($id, KeyFile::PrivateKey);

        try {
            $privateKey = $this->loadPrivateKey($pem);
        } catch (\Exception $e) {
            $this->error('Failed to load private key: ' . $e->getMessage());
            return self::FAILURE;
        }

        if ($this->option('decrypted')) {
            $newPassword = false;
        } else {
            $newPassword = $this->option('new-password') ?? false;

            if (! $newPassword) {
                $newPassword = $this->secret('Enter new password for private key');
                if (! $newPassword) {
                    $this->error('Password cannot be empty');
                    return self::FAILURE;
                }
                if ($newPassword !== $this->secret('Confirm new password')) {
                    $this->error('Passwords do not match');
                    return self::FAILURE;
                }
            }
        }

        $config->database()->keys()->putFile($id, KeyFile::PrivateKey, $privateKey->withPassword($newPassword)->toString('PKCS8'));

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
