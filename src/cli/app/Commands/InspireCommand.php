<?php

namespace App\Commands;

use App\Concerns\DiscoversConfigurationTrait;
use Illuminate\Console\Scheduling\Schedule;
use KDuma\PhpCA\Entity\KeyBuilder;
use KDuma\PhpCA\Entity\KeyEntity;
use KDuma\PhpCA\Record\KeyRecord;
use KDuma\PhpCA\Record\KeyType\Enum\EdDSACurve;
use KDuma\PhpCA\Record\KeyType\EdDSAKeyType;
use KDuma\PhpCA\Record\KeyType\RSAKeyType;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\error;
use function Laravel\Prompts\intro;
use function Termwind\render;

class InspireCommand extends Command
{
    use DiscoversConfigurationTrait;

    public function __construct()
    {
        parent::__construct();
        $this->bootDiscoversConfigurationTrait();
    }


    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'inspire {name=Artisan}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Display an inspiring quote';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $ca = $this->getCertificationAuthority();
        } catch (\InvalidArgumentException $e) {
            error($e->getMessage());
            return self::FAILURE;
        }

        $key = $ca->keys->findOrNull('ca');
        if($key === null) {
            $key = KeyBuilder::fresh(new RSAKeyType(1024))
                ->make();
            $key->id = 'ca';
            $ca->keys->save($key);
        } else {
            $key->hasPrivateKey = false;
            $ca->keys->save($key);
        }

        $k = $key->getKey();
        dd($k->verify("Test", base64_decode("l4VV0MYT34EYleeS+vszPUPyLH0MZeRr+5MkygH13gQpK6g7J1nhocahwHIsntEVPSw8eW6C8EdjpNeKoiF5SEZFJV+8hdj/vMBD/6WeRyiy4LtY/131gcWkTt24987xMrJCiutg7vwUClYTc2BBg3vWZZwYifg3Eh/9fTaLRP8=")));



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
