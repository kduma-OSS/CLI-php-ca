<?php

namespace App\Commands\Certificate;

use App\Commands\Concerns\LoadsCaConfiguration;
use App\Storage\Enums\CertificateFile;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\error;

class GetCommand extends Command
{
    use LoadsCaConfiguration;

    protected $signature = 'certificate:get {id?} {--ca= : Configuration file}';

    protected $description = 'Get issued certificate in PEM format';

    public function handle(): int
    {
        try {
            $config = $this->getCaConfig();
        } catch (\RuntimeException $e) {
            stdErr(fn () => error($e->getMessage()));

            return self::FAILURE;
        }

        $id = $this->argument('id') ?? trim(file_get_contents('php://stdin'));

        if (! $id) {
            stdErr(fn () => error('No certificate ID provided.'));

            return self::FAILURE;
        }
        $content = $config->database()->certificates()->getFile($id, CertificateFile::Certificate);

        if ($content === null) {
            stdErr(fn () => error("Certificate [{$id}] not found."));

            return self::FAILURE;
        }

        $this->output->write($content);

        return self::SUCCESS;
    }

    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
