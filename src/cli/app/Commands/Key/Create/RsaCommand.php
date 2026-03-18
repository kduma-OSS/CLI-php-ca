<?php

namespace App\Commands\Key\Create;

use App\Concerns\DiscoversConfigurationTrait;
use KDuma\PhpCA\Entity\KeyBuilder;
use KDuma\PhpCA\Record\KeyType\RSAKeyType;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\select;

class RsaCommand extends Command
{
    use DiscoversConfigurationTrait;

    protected $signature = 'key:create:rsa {--id=} {--size=}';

    protected $description = 'Create a new RSA key';

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

        $size = $this->option('size');

        if (!$size) {
            $size = select(
                label: 'Select key size',
                options: ['384', '512', '1024', '2048', '3072', '4096', '8192'],
                default: '4096',
            );
        }

        $type = new RSAKeyType(size: (int) $size);
        $entity = KeyBuilder::fresh($type)->make();

        if ($id = $this->option('id')) {
            $entity->id = $id;
        }

        $ca->keys->save($entity);

        info("RSA key created successfully.");

        $this->output->writeln($entity->id);

        return self::SUCCESS;
    }
}
