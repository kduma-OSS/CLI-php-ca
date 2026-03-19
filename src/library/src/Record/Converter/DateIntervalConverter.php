<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\Converter;

use DateInterval;
use KDuma\SimpleDAL\Typed\Contracts\Converter\FieldConverterInterface;

class DateIntervalConverter implements FieldConverterInterface
{
    public function fromStorage(mixed $value): mixed
    {
        if (! is_string($value)) {
            return null;
        }

        return new DateInterval($value);
    }

    public function toStorage(mixed $value): mixed
    {
        if ($value instanceof DateInterval) {
            return self::intervalToIso8601($value);
        }

        return $value;
    }

    private static function intervalToIso8601(DateInterval $interval): string
    {
        $parts = 'P';

        if ($interval->y > 0) {
            $parts .= $interval->y . 'Y';
        }
        if ($interval->m > 0) {
            $parts .= $interval->m . 'M';
        }
        if ($interval->d > 0) {
            $parts .= $interval->d . 'D';
        }

        $timeParts = '';
        if ($interval->h > 0) {
            $timeParts .= $interval->h . 'H';
        }
        if ($interval->i > 0) {
            $timeParts .= $interval->i . 'M';
        }
        if ($interval->s > 0) {
            $timeParts .= $interval->s . 'S';
        }

        if ($timeParts !== '') {
            $parts .= 'T' . $timeParts;
        }

        return $parts === 'P' ? 'P0D' : $parts;
    }
}
