<?php

namespace App\Commands;

use App\Concerns\DiscoversConfigurationTrait;
use LaravelZero\Framework\Commands\Command;

abstract class BaseCommand extends Command
{
    use DiscoversConfigurationTrait;

    public function __construct()
    {
        parent::__construct();
        $this->bootDiscoversConfigurationTrait();
    }
}
