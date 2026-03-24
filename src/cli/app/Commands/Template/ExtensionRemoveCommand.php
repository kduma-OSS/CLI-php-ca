<?php

namespace App\Commands\Template;

use App\Commands\BaseCommand;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\select;

class ExtensionRemoveCommand extends BaseCommand
{
    protected $signature = 'template:extension:remove {id} {extension-type?}';

    protected $description = 'Remove an extension from a template';

    public function handle(): int
    {
        $ca = $this->getCertificationAuthority();
        $template = $ca->templates->findOrNull($this->argument('id'));

        if ($template === null) {
            error('Template not found.');

            return self::FAILURE;
        }

        if (empty($template->extensions)) {
            error('Template has no extensions.');

            return self::FAILURE;
        }

        $extensionType = $this->argument('extension-type');

        if ($extensionType === null) {
            $names = array_map(fn ($ext) => $ext::name(), $template->extensions);
            $extensionType = select('Extension to remove', $names);
        }

        $found = false;
        $template->extensions = array_values(array_filter(
            $template->extensions,
            function ($ext) use ($extensionType, &$found) {
                if ($ext::name() === $extensionType && ! $found) {
                    $found = true;

                    return false;
                }

                return true;
            },
        ));

        if (! $found) {
            error("Extension \"{$extensionType}\" not found on this template.");

            return self::FAILURE;
        }

        $ca->templates->save($template);
        info("Extension \"{$extensionType}\" removed from template \"{$template->id}\".");

        return self::SUCCESS;
    }
}
