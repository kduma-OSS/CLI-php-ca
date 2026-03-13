<?php

use Laravel\Prompts\Prompt;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

if (! function_exists('stdErr')) {
    /**
     * Execute a callback with Laravel Prompts output directed to stderr.
     */
    function stdErr(callable $callback): mixed
    {
        $stderrOutput = new ConsoleOutput(OutputInterface::VERBOSITY_NORMAL);

        try {
            Prompt::setOutput($stderrOutput->getErrorOutput());

            return $callback();
        } finally {
            Prompt::setOutput(new ConsoleOutput());
        }
    }
}
