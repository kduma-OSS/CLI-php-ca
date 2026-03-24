<?php

namespace App\Commands\Certificate;

use App\Commands\BaseCommand;
use DateTimeImmutable;
use KDuma\PhpCA\Entity\RevocationEntity;
use KDuma\PhpCA\Record\Enum\RevocationReason;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\select;

class RevokeCommand extends BaseCommand
{
    protected $signature = 'certificate:revoke {id} {--reason=unspecified}';

    protected $description = 'Revoke a certificate';

    public function handle(): int
    {
        $ca = $this->getCertificationAuthority();
        $certId = $this->argument('id');

        $cert = $ca->certificates->findOrNull($certId);
        if ($cert === null) {
            error("Certificate \"{$certId}\" not found.");

            return self::FAILURE;
        }

        $reasonStr = $this->option('reason');
        $reason = RevocationReason::tryFrom($reasonStr);
        if ($reason === null) {
            $reasons = array_map(fn ($r) => $r->value, RevocationReason::cases());
            $reasonStr = select('Revocation reason', $reasons, default: 'unspecified');
            $reason = RevocationReason::from($reasonStr);
        }

        $revocation = new RevocationEntity;
        $revocation->certificateId = $certId;
        $revocation->serialNumber = $cert->serialNumber;
        $revocation->revokedAt = new DateTimeImmutable;
        $revocation->reason = $reason;
        $revocation->caCertificateId = $cert->caCertificateId;

        $ca->revocations->save($revocation);

        info("Certificate \"{$certId}\" revoked ({$reason->value}).");

        return self::SUCCESS;
    }
}
