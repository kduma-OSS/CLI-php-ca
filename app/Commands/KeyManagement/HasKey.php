<?php

namespace App\Commands\KeyManagement;

use App\Commands\Concerns\LoadsCaConfiguration;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class HasKey extends Command
{
    use LoadsCaConfiguration;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'key:has {id} {--ca= : Configuration file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check if a private key exists in the database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $config = $this->getCaConfig();

        $has = $config->database()->keys()->exists($this->argument('id'));

        if ($has) {
            $this->info('Key exists');
        } else {
            $this->error('Key does not exist');
        }

        return $has ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Define the command's schedule.
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
