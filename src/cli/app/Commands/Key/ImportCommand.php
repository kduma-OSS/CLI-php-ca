<?php

namespace App\Commands\Key;

use App\Concerns\DiscoversConfigurationTrait;
use KDuma\PhpCA\Entity\KeyBuilder;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;

class ImportCommand extends Command
{
    use DiscoversConfigurationTrait;

    protected $signature = 'key:import {pem? : Path to PEM file} {--id= : Key ID}';

    protected $description = 'Import a key from a PEM file';

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

        $pemPath = $this->argument('pem');

        if ($pemPath && file_exists($pemPath)) {
            $content = file_get_contents($pemPath);
        } else {
            $content = stream_get_contents(STDIN);
        }

        if (!$content) {
            error('No PEM data provided.');

            return self::FAILURE;
        }

        $entity = KeyBuilder::fromExisting($content)->make();

        if ($id = $this->option('id')) {
            $entity->id = $id;
        }

        $ca->keys->save($entity);

        $this->output->writeln($entity->id);

        return self::SUCCESS;
    }
}
