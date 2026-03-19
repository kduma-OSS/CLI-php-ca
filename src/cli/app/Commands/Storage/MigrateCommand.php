<?php

namespace App\Commands\Storage;

use App\Commands\BaseCommand;
use KDuma\SimpleDAL\Encryption\EncryptionMigrator;
use KDuma\SimpleDAL\Integrity\IntegrityMigrator;

use function Laravel\Prompts\info;

class MigrateCommand extends BaseCommand
{
    protected $signature = 'storage:migrate';
    protected $description = 'Migrate storage to match current integrity and encryption configuration';

    private const array ENTITY_NAMES = [
        'keys',
        'templates',
        'certificates',
        'certificate_signing_requests',
        'ca_certificate_signing_requests',
        'ca_certificates',
        'revocations',
        'certificate_revocation_lists',
        'ca_state',
    ];

    public function handle(): int
    {
        $config = $this->getCaConfiguration();
        $adapter = $config->adapter->createAdapter();

        if ($config->encryption !== null) {
            info('Migrating encryption...');
            $migrator = new EncryptionMigrator($adapter, $config->encryption->createEncryptionConfig());
            $migrator->migrate(self::ENTITY_NAMES);
            info('Encryption migration complete.');
        }

        if ($config->integrity !== null) {
            info('Migrating integrity...');
            $migrator = new IntegrityMigrator($adapter, $config->integrity->createIntegrityConfig());
            $migrator->migrate(self::ENTITY_NAMES);
            info('Integrity migration complete.');
        }

        if ($config->encryption === null && $config->integrity === null) {
            info('No integrity or encryption configuration found. Nothing to migrate.');
        }

        info('Storage migration complete.');

        return self::SUCCESS;
    }
}
