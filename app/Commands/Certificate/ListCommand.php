<?php

namespace App\Commands\Certificate;

use App\Commands\Concerns\LoadsCaConfiguration;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\error;
use function Laravel\Prompts\table;

class ListCommand extends Command
{
    use LoadsCaConfiguration;

    protected $signature = 'certificate:list {--ca= : Configuration file}';

    protected $description = 'List issued certificates';

    public function handle(): int
    {
        try {
            $config = $this->getCaConfig();
        } catch (\RuntimeException $e) {
            error($e->getMessage());

            return self::FAILURE;
        }

        $certificates = $config->database()->certificates()->all();

        if ($certificates->isEmpty()) {
            error('No certificates found.');

            return self::FAILURE;
        }

        table(
            headers: ['ID', 'Seq', 'Common Name', 'Type', 'Serial Number', 'Not Before', 'Not After', 'Revoked At'],
            rows: $certificates->map(fn ($cert) => [
                $cert->id,
                $cert->sequence,
                $cert->commonName,
                $cert->type,
                $cert->serialNumber,
                $cert->notBefore->toDateTimeString(),
                $cert->notAfter->toDateTimeString(),
                $cert->revokedAt?->toDateTimeString() ?? '',
            ])->toArray(),
        );

        return self::SUCCESS;
    }

    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
