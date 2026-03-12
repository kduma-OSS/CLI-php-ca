<?php

namespace App\Commands\KeyManagement;

use App\Commands\Concerns\LoadsCaConfiguration;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class DeletePrivateKeyCommand extends Command
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
        $config = $this->getCaConfig();

        $id = $this->argument('id');

        if (!$config->database()->keys()->exists($id)) {
            $this->error("Key with id {$id} does not exist");
            return self::FAILURE;
        }

        if (!$this->option('force') && !$this->confirm("Are you sure you want to delete key '{$id}'?")) {
            $this->info('Cancelled');
            return self::INVALID;
        }

        $config->database()->keys()->delete($id);

        $this->info("Key '{$id}' has been deleted");

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
