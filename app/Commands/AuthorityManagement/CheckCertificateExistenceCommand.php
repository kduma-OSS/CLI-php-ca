<?php

namespace App\Commands\AuthorityManagement;

use App\Commands\Concerns\LoadsCaConfiguration;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class CheckCertificateExistenceCommand extends Command
{
    use LoadsCaConfiguration;

    protected $signature = 'authority:certificate:exists {--ca= : Configuration file}';

    protected $description = 'Check if a CA certificate exists';

    public function handle(): int
    {
        $ca = $this->getCaConfig()->database()->ca();

        $has = $ca->metadata()?->certificate !== null && $ca->hasFile('certificate.pem');

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
