<?php

namespace App\Commands\Authority\Certificate;

use App\Commands\Concerns\LoadsCaConfiguration;
use App\Storage\Enums\CaFile;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\{error, info};

class ExistsCommand extends Command
{
    use LoadsCaConfiguration;

    protected $signature = 'authority:certificate:exists {--ca= : Configuration file}';

    protected $description = 'Check if a CA certificate exists';

    public function handle(): int
    {
        try {
            $config = $this->getCaConfig();
        } catch (\RuntimeException $e) {
            stdErr(fn () => error($e->getMessage()));
            return self::FAILURE;
        }

        $ca = $config->database()->ca();

        $has = $ca->metadata()?->certificate !== null && $ca->hasFile(CaFile::Certificate);

        if ($has) {
            stdErr(fn () => info('Certificate exists'));
        } else {
            stdErr(fn () => error('Certificate does not exist'));
        }

        return $has ? self::SUCCESS : self::FAILURE;
    }

    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
