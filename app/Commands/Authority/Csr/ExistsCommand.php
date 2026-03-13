<?php

namespace App\Commands\Authority\Csr;

use App\Commands\Concerns\LoadsCaConfiguration;
use App\Storage\Enums\CaFile;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\{error, info};

class ExistsCommand extends Command
{
    use LoadsCaConfiguration;

    protected $signature = 'authority:csr:exists {--ca= : Configuration file}';

    protected $description = 'Check if a CA CSR exists';

    public function handle(): int
    {
        try {
            $config = $this->getCaConfig();
        } catch (\RuntimeException $e) {
            error($e->getMessage());
            return self::FAILURE;
        }

        $ca = $config->database()->ca();

        $has = $ca->hasFile(CaFile::Csr);

        if ($has) {
            info('CSR exists');
        } else {
            error('CSR does not exist');
        }

        return $has ? self::SUCCESS : self::FAILURE;
    }

    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
