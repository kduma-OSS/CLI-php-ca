<?php

namespace App\Commands\Key;

use App\Commands\Concerns\LoadsCaConfiguration;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\error;
use function Laravel\Prompts\table;

class ListCommand extends Command
{
    use LoadsCaConfiguration;

    protected $signature = 'key:list {--ca= : Configuration file}';

    protected $description = 'List all keys';

    public function handle(): int
    {
        try {
            $config = $this->getCaConfig();
        } catch (\RuntimeException $e) {
            error($e->getMessage());

            return self::FAILURE;
        }

        $keys = $config->database()->keys()->all();

        if ($keys->isEmpty()) {
            error('No keys found.');

            return self::FAILURE;
        }

        table(
            headers: ['ID', 'Size', 'Fingerprint', 'Private', 'Created At'],
            rows: $keys->map(fn ($key) => [
                $key->id,
                $key->size ?? '',
                $key->fingerprint,
                $key->private ? 'yes' : 'no',
                $key->createdAt->toDateTimeString(),
            ])->toArray(),
        );

        return self::SUCCESS;
    }

    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
