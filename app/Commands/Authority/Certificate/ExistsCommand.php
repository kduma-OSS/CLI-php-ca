<?php

namespace App\Commands\Authority\Certificate;

use App\Commands\Concerns\LoadsCaConfiguration;
use App\Storage\Enums\CaFile;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class ExistsCommand extends Command
{
    use LoadsCaConfiguration;

    protected $signature = 'authority:certificate:exists {--ca= : Configuration file}';

    protected $description = 'Check if a CA certificate exists';

    public function handle(): int
    {
        $ca = $this->getCaConfig()->database()->ca();

        $has = $ca->metadata()?->certificate !== null && $ca->hasFile(CaFile::Certificate);

        if ($has) {
            $this->info('Certificate exists');
        } else {
            $this->error('Certificate does not exist');
        }

        return $has ? self::SUCCESS : self::FAILURE;
    }

    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
