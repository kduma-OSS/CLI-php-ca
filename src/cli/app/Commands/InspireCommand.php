<?php

namespace App\Commands;

use App\Concerns\DiscoversConfigurationTrait;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\error;
use function Laravel\Prompts\intro;
use function Termwind\render;

class InspireCommand extends Command
{
    use DiscoversConfigurationTrait;

    public function __construct()
    {
        parent::__construct();
        $this->bootDiscoversConfigurationTrait();
    }


    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'inspire {name=Artisan}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Display an inspiring quote';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $config = $this->getCaConfiguration();
        } catch (\InvalidArgumentException $e) {
            error($e->getMessage());
            return self::FAILURE;
        }

        intro('Adapter: ' . $config->adapter::getType() . ' (' . get_class($config->adapter) . ')');

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
