<?php

namespace App\Commands\Config;

use App\Concerns\DiscoversConfigurationTrait;
use App\Concerns\ManagesConfigFileTrait;
use KDuma\PhpCA\ConfigManager\Adapter\AdapterConfigurationFactory;
use KDuma\PhpCA\ConfigManager\CaConfigurationLoader;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

class InitCommand extends Command
{
    use ManagesConfigFileTrait {
        ManagesConfigFileTrait::validateConfig as traitValidateConfig;
    }
    use DiscoversConfigurationTrait;

    protected $signature = 'config:init {--force : Overwrite existing config file}';

    protected $description = 'Create a new configuration file';

    public function __construct()
    {
        parent::__construct();
        $this->bootDiscoversConfigurationTrait();
    }

    public function handle(): int
    {
        $path = getcwd() . '/' . self::CONFIG_FILE_NAME;

        if (file_exists($path) && ! $this->option('force')) {
            error("Configuration file already exists at {$path}. Use --force to overwrite.");

            return self::FAILURE;
        }

        $adapterFactory = new AdapterConfigurationFactory();
        $adapterTypes = array_keys($adapterFactory->getAdapterTypes());

        $type = select(
            label: 'Select adapter type',
            options: $adapterTypes,
            default: 'directory',
        );

        $defaultPath = match ($type) {
            'directory' => './data',
            'sqlite' => './data.sqlite',
            'zip' => './data.zip',
            default => './data',
        };

        $adapterPath = text(
            label: 'Adapter path',
            default: $defaultPath,
            required: true,
        );

        $data = [
            'adapter' => [
                'type' => $type,
                'path' => $adapterPath,
            ],
        ];

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
        file_put_contents($path, $json);

        info("Configuration file created at {$path}");

        try {
            $loader = new CaConfigurationLoader();
            $loader->load($data, dirname($path));
        } catch (\Throwable $e) {
            warning("Warning: {$e->getMessage()}");
        }

        return self::SUCCESS;
    }
}
