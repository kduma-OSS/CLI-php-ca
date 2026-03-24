<?php

namespace App\Commands\Template;

use App\Commands\BaseCommand;
use DateInterval;
use KDuma\PhpCA\Record\Converter\DateIntervalConverter;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\text;

class EditCommand extends BaseCommand
{
    protected $signature = 'template:edit {id} {--display-name=} {--validity=}';

    protected $description = 'Edit a certificate template';

    public function handle(): int
    {
        $ca = $this->getCertificationAuthority();
        $template = $ca->templates->findOrNull($this->argument('id'));

        if ($template === null) {
            error('Template not found.');

            return self::FAILURE;
        }

        $changed = false;

        if ($this->option('display-name')) {
            $template->displayName = $this->option('display-name');
            $changed = true;
        }

        if ($this->option('validity')) {
            try {
                $template->validity = new DateInterval($this->option('validity'));
                $changed = true;
            } catch (\Exception) {
                error('Invalid validity interval.');

                return self::FAILURE;
            }
        }

        if (! $changed) {
            $template->displayName = text('Display name', default: $template->displayName, required: true);

            $converter = new DateIntervalConverter;
            $currentValidity = $template->validity ? $converter->toStorage($template->validity) : '';
            $validityStr = text('Validity (ISO 8601 duration, empty to inherit from parent)', default: $currentValidity);

            if ($validityStr === '') {
                $template->validity = null;
            } else {
                try {
                    $template->validity = new DateInterval($validityStr);
                } catch (\Exception) {
                    error("Invalid validity interval: {$validityStr}");

                    return self::FAILURE;
                }
            }
        }

        $ca->templates->save($template);
        info("Template \"{$template->id}\" updated.");

        return self::SUCCESS;
    }
}
