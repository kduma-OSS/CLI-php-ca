<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\CertificateSubject;

use KDuma\PhpCA\Record\CertificateSubject\DN\BusinessCategory;
use KDuma\PhpCA\Record\CertificateSubject\DN\CommonName;
use KDuma\PhpCA\Record\CertificateSubject\DN\Country;
use KDuma\PhpCA\Record\CertificateSubject\DN\Description;
use KDuma\PhpCA\Record\CertificateSubject\DN\DnQualifier;
use KDuma\PhpCA\Record\CertificateSubject\DN\EmailAddress;
use KDuma\PhpCA\Record\CertificateSubject\DN\GenerationQualifier;
use KDuma\PhpCA\Record\CertificateSubject\DN\GivenName;
use KDuma\PhpCA\Record\CertificateSubject\DN\Initials;
use KDuma\PhpCA\Record\CertificateSubject\DN\JurisdictionCountry;
use KDuma\PhpCA\Record\CertificateSubject\DN\JurisdictionLocality;
use KDuma\PhpCA\Record\CertificateSubject\DN\JurisdictionState;
use KDuma\PhpCA\Record\CertificateSubject\DN\Locality;
use KDuma\PhpCA\Record\CertificateSubject\DN\Name;
use KDuma\PhpCA\Record\CertificateSubject\DN\Organization;
use KDuma\PhpCA\Record\CertificateSubject\DN\OrganizationalUnit;
use KDuma\PhpCA\Record\CertificateSubject\DN\PostalAddress;
use KDuma\PhpCA\Record\CertificateSubject\DN\PostalCode;
use KDuma\PhpCA\Record\CertificateSubject\DN\Pseudonym;
use KDuma\PhpCA\Record\CertificateSubject\DN\Role;
use KDuma\PhpCA\Record\CertificateSubject\DN\SerialNumber;
use KDuma\PhpCA\Record\CertificateSubject\DN\State;
use KDuma\PhpCA\Record\CertificateSubject\DN\StreetAddress;
use KDuma\PhpCA\Record\CertificateSubject\DN\Surname;
use KDuma\PhpCA\Record\CertificateSubject\DN\Title;
use KDuma\PhpCA\Record\CertificateSubject\DN\UniqueIdentifier;

class CertificateSubject
{
    /** @var array<string, class-string<BaseDN>> */
    private static array $dnTypes = [
        'CN' => CommonName::class,
        'O' => Organization::class,
        'OU' => OrganizationalUnit::class,
        'C' => Country::class,
        'ST' => State::class,
        'L' => Locality::class,
        'emailAddress' => EmailAddress::class,
        'serialNumber' => SerialNumber::class,
        'title' => Title::class,
        'description' => Description::class,
        'postalAddress' => PostalAddress::class,
        'postalCode' => PostalCode::class,
        'streetAddress' => StreetAddress::class,
        'name' => Name::class,
        'givenName' => GivenName::class,
        'SN' => Surname::class,
        'initials' => Initials::class,
        'generationQualifier' => GenerationQualifier::class,
        'dnQualifier' => DnQualifier::class,
        'pseudonym' => Pseudonym::class,
        'uniqueIdentifier' => UniqueIdentifier::class,
        'role' => Role::class,
        'businessCategory' => BusinessCategory::class,
        'jurisdictionC' => JurisdictionCountry::class,
        'jurisdictionST' => JurisdictionState::class,
        'jurisdictionL' => JurisdictionLocality::class,
    ];

    /**
     * @param  BaseDN[]  $components
     */
    public function __construct(
        public array $components,
    ) {}

    public function toString(): string
    {
        return implode(', ', array_map(
            fn (BaseDN $dn) => $dn::shortName().'='.$dn->value,
            $this->components,
        ));
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public static function fromString(string $dn): self
    {
        $components = [];
        $parts = preg_split('/\s*,\s*/', trim($dn));

        foreach ($parts as $part) {
            $pos = strpos($part, '=');
            if ($pos === false) {
                continue;
            }

            $key = trim(substr($part, 0, $pos));
            $value = trim(substr($part, $pos + 1));

            if (isset(self::$dnTypes[$key])) {
                $class = self::$dnTypes[$key];
                $components[] = new $class($value);
            }
        }

        return new self($components);
    }

    /**
     * @return BaseDN[]
     */
    public function get(string $shortName): array
    {
        return array_values(array_filter(
            $this->components,
            fn (BaseDN $dn) => $dn::shortName() === $shortName,
        ));
    }

    public function getFirst(string $shortName): ?BaseDN
    {
        $matches = $this->get($shortName);

        return $matches[0] ?? null;
    }

    public function toArray(): array
    {
        return array_map(fn (BaseDN $dn) => $dn->toArray(), $this->components);
    }

    public static function fromArray(array $data): self
    {
        $components = [];

        foreach ($data as $item) {
            $type = $item['type'] ?? null;
            $value = $item['value'] ?? null;

            if ($type === null || $value === null) {
                continue;
            }

            if (isset(self::$dnTypes[$type])) {
                $class = self::$dnTypes[$type];
                $components[] = new $class($value);
            }
        }

        return new self($components);
    }
}
