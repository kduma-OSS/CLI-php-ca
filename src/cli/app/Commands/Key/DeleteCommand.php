<?php

namespace App\Commands\Key;

use App\Concerns\DiscoversConfigurationTrait;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;

class DeleteCommand extends Command
{
    use DiscoversConfigurationTrait;

    protected $signature = 'key:delete {id} {--force : Delete without confirmation} {--private-only : Remove only the private key}';

    protected $description = 'Delete a key';

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

        if (! $ca->keys->has($id)) {
            error("Key not found.");

            return self::FAILURE;
        }

        if ($this->option('private-only')) {
            $entity = $ca->keys->find($id);

            if (! $entity->hasPrivateKey) {
                error("Key does not have a private key.");

                return self::FAILURE;
            }

            if (! $this->option('force') && ! confirm('Are you sure you want to remove the private key?')) {
                return self::FAILURE;
            }

            $entity->hasPrivateKey = false;
            $ca->keys->save($entity);

            info("Private key removed.");
        } else {
            if (! $this->option('force') && ! confirm('Are you sure you want to delete this key?')) {
                return self::FAILURE;
            }

            $ca->keys->delete($id);

            info("Key deleted.");
        }

        return self::SUCCESS;
    }
}
