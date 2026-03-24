<?php

namespace App\Commands\Template;

use App\Commands\BaseCommand;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;

class DeleteCommand extends BaseCommand
{
    protected $signature = 'template:delete {id} {--force}';

    protected $description = 'Delete a certificate template';

    public function handle(): int
    {
        $ca = $this->getCertificationAuthority();
        $id = $this->argument('id');

        if (! $ca->templates->has($id)) {
            error("Template \"{$id}\" not found.");

            return self::FAILURE;
        }

        if (! $this->option('force') && ! confirm("Delete template \"{$id}\"?")) {
            return self::SUCCESS;
        }

        $ca->templates->delete($id);
        info("Template \"{$id}\" deleted.");

        return self::SUCCESS;
    }
}
