<?php

namespace App\Commands\Template;

use App\Commands\BaseCommand;
use KDuma\PhpCA\Record\Converter\DateIntervalConverter;

use function Laravel\Prompts\error;

class ShowCommand extends BaseCommand
{
    protected $signature = 'template:show {id}';
    protected $description = 'Show template details';

    public function handle(): int
    {
        $ca = $this->getCertificationAuthority();
        $template = $ca->templates->findOrNull($this->argument('id'));

        if ($template === null) {
            error('Template not found.');
            return self::FAILURE;
        }

        $converter = new DateIntervalConverter();

        $effectiveValidity = $template->getEffectiveValidity($ca->templates);
        $effectiveExtensions = $template->getEffectiveExtensions($ca->templates);

        $rows = [
            ['ID', $template->id],
            ['Display Name', $template->displayName],
            ['Parent', $template->parentId ?? '-'],
            ['Validity (own)', $template->validity ? $converter->toStorage($template->validity) : '-'],
            ['Validity (effective)', $effectiveValidity ? $converter->toStorage($effectiveValidity) : '-'],
            ['Extensions (own)', count($template->extensions)],
            ['Extensions (effective)', count($effectiveExtensions)],
        ];

        $this->table([], $rows);

        if (! empty($effectiveExtensions)) {
            $ownNames = array_map(fn ($ext) => $ext::name(), $template->extensions);

            $this->newLine();
            $this->table(
                ['Extension', 'Critical', 'Source'],
                array_map(fn ($ext) => [
                    $ext::name(),
                    $ext->isCritical() ? 'Yes' : 'No',
                    in_array($ext::name(), $ownNames, true) ? 'own' : 'inherited',
                ], $effectiveExtensions),
            );
        }

        return self::SUCCESS;
    }
}
