<?php

namespace App\Providers;

use App\Config\CaConfigurationLoader;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use phpseclib3\Crypt\RSA;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (\Phar::running() !== '') {
            $pharOpenSslConf = \Phar::running() . '/vendor/phpseclib/phpseclib/phpseclib/openssl.cnf';
            $tmpConf = sys_get_temp_dir() . '/phpseclib_openssl.cnf';

            if (! file_exists($tmpConf) || md5_file($tmpConf) !== md5_file($pharOpenSslConf)) {
                copy($pharOpenSslConf, $tmpConf);
            }

            RSA::setOpenSSLConfigPath($tmpConf);
        }
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
