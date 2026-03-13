<?php

namespace App\Commands\Certificate;

use App\Commands\Concerns\LoadsCaConfiguration;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;

class ExistsCommand extends Command
{
    use LoadsCaConfiguration;

    protected $signature = 'certificate:exists {id} {--ca= : Configuration file}';

    protected $description = 'Check if a certificate exists';

    public function handle(): int
    {
        try {
            $config = $this->getCaConfig();
        } catch (\RuntimeException $e) {
            error($e->getMessage());

            return self::FAILURE;
        }

        $has = $config->database()->certificates()->exists($this->argument('id'));

        if ($has) {
            info('Certificate exists');
        } else {
            error('Certificate does not exist');
        }

        return $has ? self::SUCCESS : self::FAILURE;
    }

    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
