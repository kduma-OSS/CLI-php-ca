<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use KDuma\PhpCA\ConfigManager\ConfigManagerRegistry;
use KDuma\PhpCA\Record\Extension\ExtensionRegistry;
use phpseclib3\Crypt\RSA;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ConfigManagerRegistry::registerDefaults();
        ExtensionRegistry::registerDefaults();

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
        //
    }
}
