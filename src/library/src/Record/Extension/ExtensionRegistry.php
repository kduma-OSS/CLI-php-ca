<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\Extension;

use KDuma\PhpCA\Record\Extension\Extensions\AuthorityInfoAccessExtension;
use KDuma\PhpCA\Record\Extension\Extensions\AuthorityKeyIdentifierExtension;
use KDuma\PhpCA\Record\Extension\Extensions\BasicConstraintsExtension;
use KDuma\PhpCA\Record\Extension\Extensions\CertificatePoliciesExtension;
use KDuma\PhpCA\Record\Extension\Extensions\CrlDistributionPointsExtension;
use KDuma\PhpCA\Record\Extension\Extensions\ExtKeyUsageExtension;
use KDuma\PhpCA\Record\Extension\Extensions\KeyUsageExtension;
use KDuma\PhpCA\Record\Extension\Extensions\NetscapeCommentExtension;
use KDuma\PhpCA\Record\Extension\Extensions\PrivateKeyUsagePeriodExtension;
use KDuma\PhpCA\Record\Extension\Extensions\SubjectAltNameExtension;
use KDuma\PhpCA\Record\Extension\Extensions\SubjectKeyIdentifierExtension;
use KDuma\PhpCA\Record\Extension\Template\BaseExtensionTemplate;
use KDuma\PhpCA\Record\Extension\Template\Templates\AuthorityInfoAccessExtensionTemplate;
use KDuma\PhpCA\Record\Extension\Template\Templates\AuthorityKeyIdentifierExtensionTemplate;
use KDuma\PhpCA\Record\Extension\Template\Templates\BasicConstraintsExtensionTemplate;
use KDuma\PhpCA\Record\Extension\Template\Templates\CertificatePoliciesExtensionTemplate;
use KDuma\PhpCA\Record\Extension\Template\Templates\CrlDistributionPointsExtensionTemplate;
use KDuma\PhpCA\Record\Extension\Template\Templates\ExtKeyUsageExtensionTemplate;
use KDuma\PhpCA\Record\Extension\Template\Templates\KeyUsageExtensionTemplate;
use KDuma\PhpCA\Record\Extension\Template\Templates\NetscapeCommentExtensionTemplate;
use KDuma\PhpCA\Record\Extension\Template\Templates\PrivateKeyUsagePeriodExtensionTemplate;
use KDuma\PhpCA\Record\Extension\Template\Templates\SubjectAltNameExtensionTemplate;
use KDuma\PhpCA\Record\Extension\Template\Templates\SubjectKeyIdentifierExtensionTemplate;
use LogicException;

class ExtensionRegistry
{
    /** @var array<string, class-string<BaseExtension>> */
    private static array $types = [];

    /** @var array<string, class-string<BaseExtensionTemplate>> */
    private static array $templateTypes = [];

    private static bool $defaultsRegistered = false;

    /**
     * @param class-string<BaseExtension> $class
     * @param class-string<BaseExtensionTemplate>|null $templateClass
     */
    public static function register(string $class, ?string $templateClass = null): void
    {
        self::$types[$class::name()] = $class;

        if ($templateClass !== null) {
            self::$templateTypes[$templateClass::name()] = $templateClass;
        }
    }

    /**
     * @param class-string<BaseExtensionTemplate> $class
     */
    public static function registerTemplate(string $class): void
    {
        self::$templateTypes[$class::name()] = $class;
    }

    public static function registerDefaults(): void
    {
        if (self::$defaultsRegistered) {
            return;
        }

        self::$defaultsRegistered = true;

        self::register(BasicConstraintsExtension::class, BasicConstraintsExtensionTemplate::class);
        self::register(KeyUsageExtension::class, KeyUsageExtensionTemplate::class);
        self::register(ExtKeyUsageExtension::class, ExtKeyUsageExtensionTemplate::class);
        self::register(SubjectAltNameExtension::class, SubjectAltNameExtensionTemplate::class);
        self::register(CrlDistributionPointsExtension::class, CrlDistributionPointsExtensionTemplate::class);
        self::register(AuthorityInfoAccessExtension::class, AuthorityInfoAccessExtensionTemplate::class);
        self::register(SubjectKeyIdentifierExtension::class);
        self::register(AuthorityKeyIdentifierExtension::class);
        self::register(PrivateKeyUsagePeriodExtension::class, PrivateKeyUsagePeriodExtensionTemplate::class);
        self::register(NetscapeCommentExtension::class, NetscapeCommentExtensionTemplate::class);
        self::register(CertificatePoliciesExtension::class, CertificatePoliciesExtensionTemplate::class);
    }

    /**
     * @return array<string, class-string<BaseExtension>>
     */
    public static function getTypes(): array
    {
        return self::$types;
    }

    /**
     * @return array<string, class-string<BaseExtensionTemplate>>
     */
    public static function getTemplateTypes(): array
    {
        return self::$templateTypes;
    }

    /**
     * @return class-string<BaseExtension>
     */
    public static function resolve(string $name): string
    {
        return self::$types[$name] ?? throw new LogicException("Unknown extension type: {$name}");
    }

    /**
     * @return class-string<BaseExtensionTemplate>
     */
    public static function resolveTemplate(string $name): string
    {
        return self::$templateTypes[$name] ?? throw new LogicException("Unknown extension template type: {$name}");
    }

    public static function reset(): void
    {
        self::$types = [];
        self::$templateTypes = [];
        self::$defaultsRegistered = false;
    }
}
