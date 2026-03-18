<?php

namespace App\Commands\Config;

use App\Concerns\DiscoversConfigurationTrait;
use App\Concerns\ManagesConfigFileTrait;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\error;

class GetCommand extends Command
{
    use DiscoversConfigurationTrait;
    use ManagesConfigFileTrait;

    protected $signature = 'config:get {key} {--compact : Output JSON without pretty printing}';

    protected $description = 'Get a configuration value by dot-path';

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

        if (! $this->hasByDotPath($data, $key)) {
            error("Key \"{$key}\" not found.");

            return self::FAILURE;
        }

        $value = $this->getByDotPath($data, $key);

        if (is_array($value)) {
            $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
            if (! $this->option('compact')) {
                $flags |= JSON_PRETTY_PRINT;
            }
            $this->output->writeln(json_encode($value, $flags));
        } else {
            $this->output->writeln((string) $value);
        }

        return self::SUCCESS;
    }
}
