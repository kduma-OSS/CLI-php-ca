<?php

namespace App\Commands\Key\Get;

use App\Commands\Concerns\LoadsCaConfiguration;
use App\Storage\Enums\KeyFile;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class PublicCommand extends Command
{
    use LoadsCaConfiguration;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'key:get:public {id} {--ca= : Configuration file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get public key in PEM format';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $config = $this->getCaConfig();
        $repository = $config->database()->keys();

        $content = $repository->getFile($this->argument('id'), KeyFile::PublicKey);

        if ($content === null) {
            $this->error('Public key not found for ID ['.$this->argument('id').'].');

            return self::FAILURE;
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
