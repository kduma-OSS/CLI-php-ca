<?php

namespace App\Commands\Template;

use App\Commands\BaseCommand;
use DateInterval;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\text;

class CreateCommand extends BaseCommand
{
    protected $signature = 'template:create {id} {--display-name=} {--parent=} {--validity=}';

    protected $description = 'Create a new certificate template';

    public function handle(): int
    {
        $ca = $this->getCertificationAuthority();
        $id = $this->argument('id');

        if ($ca->templates->has($id)) {
            error("Template \"{$id}\" already exists.");

            return self::FAILURE;
        }

        $displayName = $this->option('display-name')
            ?? text('Display name', default: $id, required: true);

        $builder = $ca->templates->getBuilder($id)
            ->displayName($displayName);

        if ($this->option('parent')) {
            $parentId = $this->option('parent');
            if (! $ca->templates->has($parentId)) {
                error("Parent template \"{$parentId}\" not found.");

                return self::FAILURE;
            }
            $builder->parent($parentId);
        }

        $validityStr = $this->option('validity');
        if ($validityStr) {
            try {
                $builder->validity(new DateInterval($validityStr));
            } catch (\Exception) {
                error("Invalid validity interval: {$validityStr}");

                return self::FAILURE;
            }
        } elseif (! $this->option('parent')) {
            // Require validity if no parent
            $validityStr = text('Validity (ISO 8601 duration, e.g. P1Y)', default: 'P1Y', required: true);
            try {
                $builder->validity(new DateInterval($validityStr));
            } catch (\Exception) {
                error("Invalid validity interval: {$validityStr}");

                return self::FAILURE;
            }
        }

        $builder->save();

        info("Template \"{$id}\" created.");

        return self::SUCCESS;
    }
}
