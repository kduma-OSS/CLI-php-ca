<?php

namespace App\Commands\Config;

use App\Concerns\DiscoversConfigurationTrait;
use App\Concerns\ManagesConfigFileTrait;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\error;

class SetCommand extends Command
{
    use DiscoversConfigurationTrait;
    use ManagesConfigFileTrait;

    protected $signature = 'config:set {key} {value}';

    protected $description = 'Set a configuration value by dot-path';

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
        $value = $this->parseValue($this->argument('value'));

        $this->setByDotPath($data, $key, $value);
        $this->writeConfigData($data);
        $this->validateConfig($data);

        return self::SUCCESS;
    }
}
