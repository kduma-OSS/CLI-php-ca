<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\Extension\Extensions;

use KDuma\PhpCA\Record\Extension\BaseExtension;

readonly class KeyUsageExtension extends BaseExtension
{
    public function __construct(
        public bool $digitalSignature = false,
        public bool $nonRepudiation = false,
        public bool $keyEncipherment = false,
        public bool $dataEncipherment = false,
        public bool $keyAgreement = false,
        public bool $keyCertSign = false,
        public bool $cRLSign = false,
        public bool $encipherOnly = false,
        public bool $decipherOnly = false,
        private bool $critical = true,
    ) {}

    public static function oid(): string
    {
        return '2.5.29.15';
    }

    public static function name(): string
    {
        return 'key-usage';
    }

    public function isCritical(): bool
    {
        return $this->critical;
    }

    public function toArray(): array
    {
        return [
            'name' => self::name(),
            'critical' => $this->critical,
            'digital_signature' => $this->digitalSignature,
            'non_repudiation' => $this->nonRepudiation,
            'key_encipherment' => $this->keyEncipherment,
            'data_encipherment' => $this->dataEncipherment,
            'key_agreement' => $this->keyAgreement,
            'key_cert_sign' => $this->keyCertSign,
            'crl_sign' => $this->cRLSign,
            'encipher_only' => $this->encipherOnly,
            'decipher_only' => $this->decipherOnly,
        ];
    }

    public static function fromArray(array $data): static
    {
        return new static(
            digitalSignature: $data['digital_signature'] ?? false,
            nonRepudiation: $data['non_repudiation'] ?? false,
            keyEncipherment: $data['key_encipherment'] ?? false,
            dataEncipherment: $data['data_encipherment'] ?? false,
            keyAgreement: $data['key_agreement'] ?? false,
            keyCertSign: $data['key_cert_sign'] ?? false,
            cRLSign: $data['crl_sign'] ?? false,
            encipherOnly: $data['encipher_only'] ?? false,
            decipherOnly: $data['decipher_only'] ?? false,
            critical: $data['critical'] ?? true,
        );
    }
}
