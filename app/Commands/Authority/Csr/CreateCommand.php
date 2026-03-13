<?php

namespace App\Commands\Authority\Csr;

use App\Commands\Concerns\LoadsCaConfiguration;
use App\Commands\Concerns\LoadsPrivateKey;
use App\Storage\Enums\CaFile;
use App\Storage\Enums\KeyFile;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use phpseclib3\File\X509;

use function Laravel\Prompts\error;

class CreateCommand extends Command
{
    use LoadsCaConfiguration;
    use LoadsPrivateKey;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'authority:csr:create {key_id} {distinguished_name} {--ca= : Configuration file} {--password= : Password} {--force : Overwrite existing CSR} {--ignore-existing-certificate : Create CSR even if a certificate already exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a certificate signing request';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $config = $this->getCaConfig();
        } catch (\RuntimeException $e) {
            stdErr(fn () => error($e->getMessage()));
            return self::FAILURE;
        }

        $ca = $config->database()->ca();

        $hasCertificate = $ca->metadata()?->certificate !== null && $ca->hasFile(CaFile::Certificate);
        if ($hasCertificate && !$this->option('ignore-existing-certificate')) {
            stdErr(fn () => error('A certificate already exists. Use --ignore-existing-certificate to create a CSR anyway.'));
            return self::FAILURE;
        }

        if ($ca->hasFile(CaFile::Csr) && !$this->option('force')) {
            stdErr(fn () => error('A CSR already exists. Use --force to overwrite.'));
            return self::FAILURE;
        }

        $distinguished_name = $this->argument('distinguished_name');
        if (! (new X509)->setDN($distinguished_name)) {
            stdErr(fn () => error('Invalid distinguished name format.'));
            return self::FAILURE;
        }

        $key_id = $this->argument('key_id');
        if (! $config->database()->keys()->exists($key_id)) {
            stdErr(fn () => error("Key [{$key_id}] does not exist."));

            return self::FAILURE;
        }

        $pem = $config->database()->keys()->getFile($key_id, KeyFile::PrivateKey);

        try {
            $key = $this->loadPrivateKey($pem);
        } catch (\Exception $e) {
            stdErr(fn () => error('Failed to load private key: ' . $e->getMessage()));
            return self::FAILURE;
        }

        $x509 = new X509;
        $x509->setDN($distinguished_name);
        $x509->setPrivateKey($key);

        $csr = $x509->signCSR();
        $csrPem = $x509->saveCSR($csr);

        $ca->putFile(CaFile::Csr, $csrPem);

        return self::SUCCESS;
    }

    /**
     * Define the command's schedule.
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
