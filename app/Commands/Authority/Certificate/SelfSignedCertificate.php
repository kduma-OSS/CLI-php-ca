<?php

namespace App\Commands\Authority\Certificate;

use App\Commands\Concerns\LoadsCaConfiguration;
use App\Commands\Concerns\LoadsPrivateKey;
use App\Storage\Entities\CaCertificateDetails;
use App\Storage\Entities\CaMetadata;
use App\Storage\Enums\CaFile;
use App\Storage\Enums\KeyFile;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use phpseclib3\Crypt\Random;
use phpseclib3\File\X509;
use phpseclib3\Math\BigInteger;

class SelfSignedCertificate extends Command
{
    use LoadsCaConfiguration;
    use LoadsPrivateKey;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'authority:certificate:self-signed {key_id} {distinguished_name} {--ca= : Configuration file} {--password= : Password} {--path-length-constraint= : Certification authority path length constraint} {--serial-number= : HEX formatted serial number} {--validity=+25 years : Certificate validity period} {--force : Overwrite existing certificate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a self-signed certificate';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $config = $this->getCaConfig();
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $ca = $config->database()->ca();

        if ($ca->metadata()?->certificate !== null && !$this->option('force')) {
            $this->error('A certificate already exists. Use --force to overwrite.');
            return self::FAILURE;
        }

        $distinguished_name = $this->argument('distinguished_name');
        if (! (new X509)->setDN($distinguished_name)) {
            $this->error('Invalid distinguished name format.');
            return self::FAILURE;
        }

        $serial_number = $this->option('serial-number');
        if ($serial_number !== null) {
            if (! ctype_xdigit($serial_number)) {
                $this->error('Serial number must be a valid hexadecimal string.');
                return self::FAILURE;
            }
        } else if($config->certificationAuthority->randomSerialNumbers) {
            $serial_number = new BigInteger(Random::string(20) & ("\x7F" . str_repeat("\xFF", 19)), 256)->toHex();
        } else {
            $existingSerial = $ca->metadata()?->certificate?->serial_number;
            $serial_number = $existingSerial !== null
                ? (new BigInteger($existingSerial, 16))->add(new BigInteger(1))->toHex()
                : "1";
        }

        $key_id = $this->argument('key_id');
        if (! $config->database()->keys()->exists($key_id)) {
            $this->error("Key [{$key_id}] does not exist.");

            return self::FAILURE;
        }

        $pem = $config->database()->keys()->getFile($key_id, KeyFile::PrivateKey);


        try {
            $key = $this->loadPrivateKey($pem);
        } catch (\Exception $e) {
            $this->error('Failed to load private key: ' . $e->getMessage());
            return self::FAILURE;
        }

        $path_length_constraint = $this->option('path-length-constraint');
        if ($path_length_constraint !== null) {
            $path_length_constraint = (int) $path_length_constraint;
            if ($path_length_constraint < 0) {
                $this->error('Path length constraint must be greater than or equal to zero.');
                return self::FAILURE;
            }
        }

        $rootSubject = new X509;
        $rootSubject->setDN($distinguished_name);
        $rootSubject->setPublicKey($key->getPublicKey());

        $rootIssuer = new X509;
        $rootIssuer->setPrivateKey($key);
        $rootIssuer->setDN($rootSubject->getDN());

        $validFrom = new CarbonImmutable();
        $validTo = new CarbonImmutable($this->option('validity'));

        $x509 = new X509;
        $x509->setSerialNumber($serial_number, 16);
        $x509->makeCA();
        if ($path_length_constraint !== null) {
            $x509->setExtensionValue('id-ce-basicConstraints', [
                'cA' => true,
                'pathLenConstraint' => $path_length_constraint,
            ], true);
        }
        $x509->setStartDate($validFrom);
        $x509->setEndDate($validTo);
        $result = $x509->sign($rootIssuer, $rootSubject);

        $ca->putFile(CaFile::Certificate, $x509->saveX509($result));
        $ca->saveMetadata(new CaMetadata(
            key_id: $key_id,
            certificate: new CaCertificateDetails(
                serial_number: $serial_number,
                distinguished_name: $distinguished_name,
                path_length_constraint: $path_length_constraint,
                valid_from: $validFrom,
                valid_to: $validTo,
            ),
        ));

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
