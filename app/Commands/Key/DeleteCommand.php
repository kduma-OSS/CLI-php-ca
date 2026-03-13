<?php

namespace App\Commands\Key;

use App\Commands\Concerns\LoadsCaConfiguration;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\{error, info, confirm};

class DeleteCommand extends Command
{
    use LoadsCaConfiguration;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'key:delete {id} {--ca= : Configuration file} {--force : Force delete, without asking for confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete a private key from the database';

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

        $id = $this->argument('id');

        if (!$config->database()->keys()->exists($id)) {
            error("Key with id {$id} does not exist");
            return self::FAILURE;
        }

        if (!$this->option('force') && !confirm("Are you sure you want to delete key '{$id}'?")) {
            info('Cancelled');
            return self::INVALID;
        }

        $config->database()->keys()->delete($id);

        info("Key '{$id}' has been deleted");

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
