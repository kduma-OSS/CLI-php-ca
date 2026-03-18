<?php

namespace App\Commands\Config;

use App\Concerns\DiscoversConfigurationTrait;
use App\Concerns\ManagesConfigFileTrait;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\error;

class UnsetCommand extends Command
{
    use DiscoversConfigurationTrait;
    use ManagesConfigFileTrait;

    protected $signature = 'config:unset {key}';

    protected $description = 'Remove a configuration value by dot-path';

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

        if (! $this->unsetByDotPath($data, $key)) {
            error("Key \"{$key}\" not found.");

            return self::FAILURE;
        }

        $this->writeConfigData($data);
        $this->validateConfig($data);

        return self::SUCCESS;
    }
}
