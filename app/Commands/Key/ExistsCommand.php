<?php

namespace App\Commands\Key;

use App\Commands\Concerns\LoadsCaConfiguration;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\{error, info};

class ExistsCommand extends Command
{
    use LoadsCaConfiguration;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'key:exists {id} {--ca= : Configuration file}';

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
        try {
            $config = $this->getCaConfig();
        } catch (\RuntimeException $e) {
            error($e->getMessage());
            return self::FAILURE;
        }

        $has = $config->database()->keys()->exists($this->argument('id'));

        if ($has) {
            info('Key exists');
        } else {
            error('Key does not exist');
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
