<?php

namespace App\Commands\Config;

use App\Concerns\DiscoversConfigurationTrait;
use App\Concerns\ManagesConfigFileTrait;
use KDuma\PhpCA\ConfigManager\CaConfigurationLoader;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;

class ValidateCommand extends Command
{
    use DiscoversConfigurationTrait;
    use ManagesConfigFileTrait;

    protected $signature = 'config:validate';

    protected $description = 'Validate the configuration file';

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

        try {
            $loader = new CaConfigurationLoader;
            $loader->load($data, dirname($this->getCaConfigPath()));
        } catch (\Throwable $e) {
            error("Validation failed: {$e->getMessage()}");

            return self::FAILURE;
        }

        info('Configuration is valid.');

        return self::SUCCESS;
    }
}
