<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\Extension\Template\Templates;

use KDuma\PhpCA\Record\Extension\BaseExtension;
use KDuma\PhpCA\Record\Extension\Extensions\KeyUsageExtension;
use KDuma\PhpCA\Record\Extension\Resolver\IssuanceContext;
use KDuma\PhpCA\Record\Extension\Template\BaseExtensionTemplate;

class KeyUsageExtensionTemplate extends BaseExtensionTemplate
{
    public function __construct(
        public readonly bool $digitalSignature = false,
        public readonly bool $nonRepudiation = false,
        public readonly bool $keyEncipherment = false,
        public readonly bool $dataEncipherment = false,
        public readonly bool $keyAgreement = false,
        public readonly bool $keyCertSign = false,
        public readonly bool $cRLSign = false,
        public readonly bool $encipherOnly = false,
        public readonly bool $decipherOnly = false,
        public readonly bool $critical = true,
    ) {}

    public static function name(): string
    {
        return 'key-usage';
    }

    public function resolve(IssuanceContext $context): BaseExtension
    {
        return new KeyUsageExtension(
            digitalSignature: $this->digitalSignature,
            nonRepudiation: $this->nonRepudiation,
            keyEncipherment: $this->keyEncipherment,
            dataEncipherment: $this->dataEncipherment,
            keyAgreement: $this->keyAgreement,
            keyCertSign: $this->keyCertSign,
            cRLSign: $this->cRLSign,
            encipherOnly: $this->encipherOnly,
            decipherOnly: $this->decipherOnly,
            critical: $this->critical,
        );
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
        $boolFields = ['digital_signature', 'non_repudiation', 'key_encipherment', 'data_encipherment',
            'key_agreement', 'key_cert_sign', 'crl_sign', 'encipher_only', 'decipher_only', 'critical'];

        foreach ($boolFields as $field) {
            if (isset($data[$field]) && ! is_bool($data[$field])) {
                throw new \InvalidArgumentException("key-usage: \"{$field}\" must be a boolean.");
            }
        }

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
