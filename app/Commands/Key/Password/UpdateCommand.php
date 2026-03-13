<?php

namespace App\Commands\Key\Password;

use App\Commands\Concerns\LoadsCaConfiguration;
use App\Commands\Concerns\LoadsPrivateKey;
use App\Storage\Enums\KeyFile;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\{error, password};

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
            stdErr(fn () => error($e->getMessage()));
            return self::FAILURE;
        }

        $id = $this->argument('id');

        if (! $config->database()->keys()->exists($id)) {
            stdErr(fn () => error("Key [{$id}] does not exist."));
            return self::FAILURE;
        }

        $pem = $config->database()->keys()->getFile($id, KeyFile::PrivateKey);

        try {
            $privateKey = $this->loadPrivateKey($pem);
        } catch (\Exception $e) {
            stdErr(fn () => error('Failed to load private key: ' . $e->getMessage()));
            return self::FAILURE;
        }

        if ($this->option('decrypted')) {
            $newPassword = false;
        } else {
            $newPassword = $this->option('new-password') ?? false;

            if (! $newPassword) {
                $newPassword = stdErr(fn () => password(label: 'Enter new password for private key', required: true));
                if ($newPassword !== stdErr(fn () => password(label: 'Confirm new password', required: true))) {
                    stdErr(fn () => error('Passwords do not match'));
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
