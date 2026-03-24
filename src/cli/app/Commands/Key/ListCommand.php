<?php

namespace App\Commands\Key;

use App\Concerns\DiscoversConfigurationTrait;
use KDuma\PhpCA\Record\KeyType\DSAKeyType;
use KDuma\PhpCA\Record\KeyType\ECDSAKeyType;
use KDuma\PhpCA\Record\KeyType\EdDSAKeyType;
use KDuma\PhpCA\Record\KeyType\RSAKeyType;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\table;

class ListCommand extends Command
{
    use DiscoversConfigurationTrait;

    protected $signature = 'key:list';

    protected $description = 'List all keys';

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

        $entities = $ca->keys->all();

        if (empty($entities)) {
            info('No keys found.');

            return self::SUCCESS;
        }

        $rows = [];

        foreach ($entities as $entity) {
            $typeStr = match (true) {
                $entity->type instanceof RSAKeyType => 'RSA',
                $entity->type instanceof DSAKeyType => 'DSA',
                $entity->type instanceof ECDSAKeyType => 'ECDSA',
                $entity->type instanceof EdDSAKeyType => 'EdDSA',
                default => 'Unknown',
            };

            $details = match (true) {
                $entity->type instanceof RSAKeyType => $entity->type->size.' bits',
                $entity->type instanceof DSAKeyType => $entity->type->parameters->value,
                $entity->type instanceof ECDSAKeyType => $entity->type->curve->value,
                $entity->type instanceof EdDSAKeyType => $entity->type->curve->value,
                default => '',
            };

            $rows[] = [
                $entity->id,
                $typeStr,
                $details,
                $entity->fingerprint,
                $entity->hasPrivateKey ? 'Yes' : 'No',
                $entity->createdAt?->format('Y-m-d H:i:s') ?? '',
            ];
        }

        table(
            headers: ['ID', 'Type', 'Details', 'Fingerprint', 'Private', 'Created At'],
            rows: $rows,
        );

        return self::SUCCESS;
    }
}
