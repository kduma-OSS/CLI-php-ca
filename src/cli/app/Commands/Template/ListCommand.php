<?php

namespace App\Commands\Template;

use App\Commands\BaseCommand;
use KDuma\PhpCA\Record\Converter\DateIntervalConverter;

use function Laravel\Prompts\info;

class ListCommand extends BaseCommand
{
    protected $signature = 'template:list';
    protected $description = 'List all certificate templates';

    public function handle(): int
    {
        $ca = $this->getCertificationAuthority();
        $templates = $ca->templates->all();

        if (empty($templates)) {
            info('No templates found.');
            return self::SUCCESS;
        }

        $converter = new DateIntervalConverter();

        $this->table(
            ['ID', 'Display Name', 'Parent', 'Validity'],
            array_map(fn ($t) => [
                $t->id,
                $t->displayName,
                $t->parentId ?? '-',
                $t->validity ? $converter->toStorage($t->validity) : '(inherited)',
            ], $templates),
        );

        return self::SUCCESS;
    }
}
