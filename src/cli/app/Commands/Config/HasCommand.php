<?php

namespace App\Commands\Config;

use App\Concerns\DiscoversConfigurationTrait;
use App\Concerns\ManagesConfigFileTrait;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\error;

class HasCommand extends Command
{
    use DiscoversConfigurationTrait;
    use ManagesConfigFileTrait;

    protected $signature = 'config:has {key}';

    protected $description = 'Check if a configuration key exists';

    public function __construct()
    {
        parent::__construct();
        $this->bootDiscoversConfigurationTrait();
    }

    public function handle(): int
    {
        try {
            $data = $this->readConfigData();
        } catch (\Throwable $e) {
            error($e->getMessage());

            return self::FAILURE;
        }

        $key = $this->argument('key');
        $exists = $this->hasByDotPath($data, $key);

        $this->output->writeln($exists ? 'true' : 'false');

        return $exists ? self::SUCCESS : self::FAILURE;
    }
}
