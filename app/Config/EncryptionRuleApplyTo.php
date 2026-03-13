<?php

namespace App\Config;

use App\Storage\Enums\CaFile;
use App\Storage\Enums\CertificateFile;
use App\Storage\Enums\KeyFile;
use App\Storage\Infrastructure\RepositoryFile;
use InvalidArgumentException;

readonly class EncryptionRuleApplyTo
{
    /**
     * @param  array<string>|null  $ids  null means all IDs
     */
    public function __construct(
        public RepositoryFile $file,
        public ?array $ids,
    ) {}

    /**
     * @param  true|array<string>  $value
     */
    public static function fromEntry(string $key, true|array $value): self
    {
        return new self(
            file: self::resolveRepositoryFile($key),
            ids: $value === true ? null : $value,
        );
    }

    private static function resolveRepositoryFile(string $key): RepositoryFile
    {
        $enumMap = [
            'KeyFile' => KeyFile::class,
            'CertificateFile' => CertificateFile::class,
            'CaFile' => CaFile::class,
        ];

        $parts = explode('::', $key, 2);

        if (count($parts) !== 2) {
            throw new InvalidArgumentException("Invalid apply_to key format: '{$key}'. Expected format: 'EnumName::CaseName'.");
        }

        [$enumName, $caseName] = $parts;

        $enumClass = $enumMap[$enumName] ?? null;

        if ($enumClass === null) {
            throw new InvalidArgumentException("Unknown file type: '{$enumName}'. Valid types: ".implode(', ', array_keys($enumMap)).'.');
        }

        foreach ($enumClass::cases() as $case) {
            if ($case->name === $caseName) {
                return $case;
            }
        }

        throw new InvalidArgumentException("Unknown case '{$caseName}' for {$enumName}. Valid cases: ".implode(', ', array_map(fn ($c) => $c->name, $enumClass::cases())).'.');
    }
}
