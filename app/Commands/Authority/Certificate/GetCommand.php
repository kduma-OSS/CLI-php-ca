<?php

namespace App\Commands\Authority\Certificate;

use App\Commands\Concerns\LoadsCaConfiguration;
use App\Storage\Enums\CaFile;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\error;

class GetCommand extends Command
{
    use LoadsCaConfiguration;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'authority:certificate:get {--ca= : Configuration file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get CA certificate in PEM format';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $config = $this->getCaConfig();
        } catch (\RuntimeException $e) {
            stdErr(fn () => error($e->getMessage()));
            return self::FAILURE;
        }

        $ca = $config->database()->ca();

        $content = $ca->getFile(CaFile::Certificate);

        if ($content === null) {
            stdErr(fn () => error('CA certificate not found.'));

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
