<?php

namespace App\Commands\Key\Get;

use App\Concerns\DiscoversConfigurationTrait;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\error;

class PublicCommand extends Command
{
    use DiscoversConfigurationTrait;

    protected $signature = 'key:get:public {id}';

    protected $description = 'Get the public key PEM';

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

        $this->output->write($entity->publicKey);

        return self::SUCCESS;
    }
}
