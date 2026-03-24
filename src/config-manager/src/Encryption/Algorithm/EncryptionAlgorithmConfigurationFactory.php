<?php

declare(strict_types=1);

namespace KDuma\PhpCA\ConfigManager\Encryption\Algorithm;

use InvalidArgumentException;
use KDuma\PhpCA\ConfigManager\ConfigManagerRegistry;
use KDuma\PhpCA\ConfigManager\Encryption\Algorithm\Attributes\EncryptionAlgorithmConfiguration;
use LogicException;
use Spatie\Attributes\Attributes;

class EncryptionAlgorithmConfigurationFactory
{
    /**
     * @return array<string, class-string<BaseEncryptionAlgorithmConfiguration>>
     */
    public function getTypes(): array
    {
        return ConfigManagerRegistry::getEncryptionAlgorithmTypes();
    }

    public function fromArray(array $data, string $basePath): BaseEncryptionAlgorithmConfiguration
    {
        if (! isset($data['type']) || ! is_string($data['type'])) {
            throw new InvalidArgumentException('Encryption algorithm configuration must contain a "type" field.');
        }

        $type = $data['type'];
        $types = $this->getTypes();

        if (! isset($types[$type])) {
            $supported = implode(', ', array_keys($types));
            throw new InvalidArgumentException("Unknown encryption algorithm type \"{$type}\". Supported types: {$supported}.");
        }

        return $types[$type]::fromArray($data, $basePath);
    }

    /**
     * @param  class-string<BaseEncryptionAlgorithmConfiguration>  $class
     */
    public static function getTypeForClass(string $class): string
    {
        $attr = Attributes::get($class, EncryptionAlgorithmConfiguration::class);

        if ($attr !== null) {
            return $attr->type;
        }

        throw new LogicException("Missing #[EncryptionAlgorithmConfiguration] attribute on {$class}.");
    }
}
