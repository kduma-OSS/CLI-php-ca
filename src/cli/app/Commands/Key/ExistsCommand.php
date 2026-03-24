<?php

namespace App\Commands\Key;

use App\Concerns\DiscoversConfigurationTrait;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;

class ExistsCommand extends Command
{
    use DiscoversConfigurationTrait;

    protected $signature = 'key:exists {id}';

    protected $description = 'Check if a key exists';

    public function __construct()
    {
        parent::__construct();
        $this->bootDiscoversConfigurationTrait();
    }

    public function handle(): int
    {
        try {
            $ca = $this->getCertificationAuthority();
        } catch (\InvalidArgumentException $e) {
            error($e->getMessage());

            return self::FAILURE;
        }

        $id = $this->argument('id');

        if ($ca->keys->has($id)) {
            info('Key exists.');

            return self::SUCCESS;
        }

        error('Key not found.');

        return self::FAILURE;
    }
}
