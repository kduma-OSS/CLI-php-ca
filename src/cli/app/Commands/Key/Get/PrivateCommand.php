<?php

namespace App\Commands\Key\Get;

use App\Concerns\DiscoversConfigurationTrait;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\error;

class PrivateCommand extends Command
{
    use DiscoversConfigurationTrait;

    protected $signature = 'key:get:private {id}';

    protected $description = 'Get the private key PEM';

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
        $entity = $ca->keys->find($id);

        if (! $entity->hasPrivateKey) {
            error('Private key not found.');

            return self::FAILURE;
        }

        $this->output->write($entity->privateKey);

        return self::SUCCESS;
    }
}
