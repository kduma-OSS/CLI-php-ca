<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use KDuma\PhpCA\ConfigManager\ConfigManagerRegistry;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ConfigManagerRegistry::registerDefaults();
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }
}
