<?php

namespace App\Commands\Key\Get;

use App\Commands\Concerns\LoadsCaConfiguration;
use App\Commands\Concerns\LoadsPrivateKey;
use App\Storage\Enums\KeyFile;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class PrivateCommand extends Command
{
    use LoadsCaConfiguration;
    use LoadsPrivateKey;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'key:get:private {id} {--ca= : Configuration file} {--decrypted : Decrypt private key} {--password= : Password}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get private key in PEM format';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $config = $this->getCaConfig();
        $repository = $config->database()->keys();

        $content = $repository->getFile($this->argument('id'), KeyFile::PrivateKey);

        if ($content === null) {
            $this->error('Private key not found for ID ['.$this->argument('id').'].');

            return self::FAILURE;
        }

        if ($this->option('decrypted')) {
            try {
                $key = $this->loadPrivateKey($content);
            } catch (\Exception $e) {
                $this->error('Failed to decrypt private key: '.$e->getMessage());

                return self::FAILURE;
            }

            $content = $key->withPassword(false)->toString('PKCS8');
        }

        $this->output->write($content);

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
