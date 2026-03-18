<?php

declare(strict_types=1);

uses(Tests\TestCase::class);

it('inspires artisans', function () {
    $this->artisan('inspire')->assertExitCode(0);
});
