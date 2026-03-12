<?php

namespace App\Providers;

use App\Config\CaConfigurationLoader;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(CaConfigurationLoader::class, function ($app) {
            return new CaConfigurationLoader($app->make(Filesystem::class));
        });
    }
}
