<?php

declare(strict_types=1);

namespace KDuma\PhpCA\ConfigManager\Integrity;

use KDuma\PhpCA\ConfigManager\Integrity\Hasher\BaseHasherConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Hasher\HasherConfigurationFactory;
use KDuma\PhpCA\ConfigManager\Integrity\Signer\BaseSignerConfiguration;
use KDuma\PhpCA\ConfigManager\Integrity\Signer\SignerConfigurationFactory;
use KDuma\SimpleDAL\Integrity\FailureMode;
use KDuma\SimpleDAL\Integrity\IntegrityConfig;

readonly class IntegrityConfiguration
{
    public function __construct(
        public ?BaseHasherConfiguration $hasher = null,
        public ?BaseSignerConfiguration $signer = null,
        public FailureMode $onChecksumFailure = FailureMode::Throw,
        public FailureMode $onSignatureFailure = FailureMode::Throw,
        public FailureMode $onMissingIntegrity = FailureMode::Throw,
    ) {}

    public function createIntegrityConfig(): IntegrityConfig
    {
        return new IntegrityConfig(
            hasher: $this->hasher?->createHasher(),
            signer: $this->signer?->createSigner(),
            onChecksumFailure: $this->onChecksumFailure,
            onSignatureFailure: $this->onSignatureFailure,
            onMissingIntegrity: $this->onMissingIntegrity,
        );
    }

    public static function fromArray(array $data, string $basePath): static
    {
        $hasher = null;
        if (isset($data['hasher']) && is_array($data['hasher'])) {
            $hasher = (new HasherConfigurationFactory())->fromArray($data['hasher']);
        }

        $signer = null;
        if (isset($data['signer']) && is_array($data['signer'])) {
            $signer = (new SignerConfigurationFactory())->fromArray($data['signer'], $basePath);
        }

        return new static(
            hasher: $hasher,
            signer: $signer,
            onChecksumFailure: self::parseFailureMode($data['on_checksum_failure'] ?? 'throw'),
            onSignatureFailure: self::parseFailureMode($data['on_signature_failure'] ?? 'throw'),
            onMissingIntegrity: self::parseFailureMode($data['on_missing_integrity'] ?? 'throw'),
        );
    }

    public function toArray(): array
    {
        $result = [];

        if ($this->hasher !== null) {
            $result['hasher'] = $this->hasher->toArray();
        }

        if ($this->signer !== null) {
            $result['signer'] = $this->signer->toArray();
        }

        $result['on_checksum_failure'] = self::failureModeToString($this->onChecksumFailure);
        $result['on_signature_failure'] = self::failureModeToString($this->onSignatureFailure);
        $result['on_missing_integrity'] = self::failureModeToString($this->onMissingIntegrity);

        return $result;
    }

    private static function parseFailureMode(string $value): FailureMode
    {
        return match ($value) {
            'throw' => FailureMode::Throw,
            'ignore' => FailureMode::Ignore,
            default => throw new \InvalidArgumentException("Unknown failure mode: \"{$value}\". Supported: throw, ignore."),
        };
    }

    private static function failureModeToString(FailureMode $mode): string
    {
        return match ($mode) {
            FailureMode::Throw => 'throw',
            FailureMode::Ignore => 'ignore',
        };
    }
}
