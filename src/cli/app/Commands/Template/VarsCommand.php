<?php

namespace App\Commands\Template;

use App\Commands\BaseCommand;
use KDuma\PhpCA\Record\Extension\Resolver\InputMultipleResolver;
use KDuma\PhpCA\Record\Extension\Resolver\InputResolver;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;

class VarsCommand extends BaseCommand
{
    protected $signature = 'template:vars {id}';
    protected $description = 'List input variables required by a template';

    public function handle(): int
    {
        $ca = $this->getCertificationAuthority();
        $template = $ca->templates->findOrNull($this->argument('id'));

        if ($template === null) {
            error('Template not found.');
            return self::FAILURE;
        }

        $allInputs = [];
        $effectiveExtensions = $template->getEffectiveExtensions($ca->templates);

        foreach ($effectiveExtensions as $ext) {
            $inputs = $ext->getRequiredInputs();
            foreach ($inputs as $alias => $resolver) {
                $allInputs[] = [
                    'alias' => $alias,
                    'label' => $resolver->label,
                    'type' => $resolver instanceof InputMultipleResolver ? 'multiple' : 'single',
                    'default' => $resolver instanceof InputResolver ? ($resolver->default ?? '-') : '-',
                    'extension' => $ext::name(),
                ];
            }
        }

        if (empty($allInputs)) {
            info('Template has no input variables.');
            return self::SUCCESS;
        }

        $this->table(
            ['Alias (--var key)', 'Label', 'Type', 'Default', 'Extension'],
            array_map(fn ($i) => [
                $i['alias'],
                $i['label'],
                $i['type'],
                $i['default'],
                $i['extension'],
            ], $allInputs),
        );

        return self::SUCCESS;
    }
}
