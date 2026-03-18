<?php

namespace App\Commands\Key\Create;

use App\Concerns\DiscoversConfigurationTrait;
use KDuma\PhpCA\Entity\KeyBuilder;
use KDuma\PhpCA\Record\KeyType\DSAKeyType;
use KDuma\PhpCA\Record\KeyType\Enum\DsaParameterSize;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\select;

class DsaCommand extends Command
{
    use DiscoversConfigurationTrait;

    protected $signature = 'key:create:dsa {--id=} {--parameters=}';

    protected $description = 'Create a new DSA key';

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

        $parameters = $this->option('parameters');

        if (!$parameters) {
            $parameters = select(
                label: 'Select DSA parameter size',
                options: array_map(fn (DsaParameterSize $case) => $case->value, DsaParameterSize::cases()),
                default: '2048-256',
            );
        }

        $type = new DSAKeyType(parameters: DsaParameterSize::from($parameters));
        $entity = KeyBuilder::fresh($type)->make();

        if ($id = $this->option('id')) {
            $entity->id = $id;
        }

        $ca->keys->save($entity);

        info("DSA key created successfully.");

        $this->output->writeln($entity->id);

        return self::SUCCESS;
    }
}
