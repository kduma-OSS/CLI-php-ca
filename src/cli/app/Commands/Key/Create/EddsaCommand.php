<?php

namespace App\Commands\Key\Create;

use App\Concerns\DiscoversConfigurationTrait;
use KDuma\PhpCA\Entity\KeyBuilder;
use KDuma\PhpCA\Record\KeyType\EdDSAKeyType;
use KDuma\PhpCA\Record\KeyType\Enum\EdDSACurve;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\select;

class EddsaCommand extends Command
{
    use DiscoversConfigurationTrait;

    protected $signature = 'key:create:eddsa {--id=} {--curve=}';

    protected $description = 'Create a new EdDSA key';

    public function __construct()
    {
        parent::__construct();
        $this->bootDiscoversConfigurationTrait();
    }

    public function handle(): int
    {
        try {
            $ca = $this->getCertificationAuthority();
        } catch (\Throwable $e) {
            error($e->getMessage());

            return self::FAILURE;
        }

        $curve = $this->option('curve');

        if (!$curve) {
            $curve = select(
                label: 'Select EdDSA curve',
                options: array_map(fn (EdDSACurve $case) => $case->value, EdDSACurve::cases()),
                default: 'Ed25519',
            );
        }

        $type = new EdDSAKeyType(curve: EdDSACurve::from($curve));
        $entity = KeyBuilder::fresh($type)->make();

        if ($id = $this->option('id')) {
            $entity->id = $id;
        }

        $ca->keys->save($entity);

        info("EdDSA key created successfully.");

        $this->output->writeln($entity->id);

        return self::SUCCESS;
    }
}
