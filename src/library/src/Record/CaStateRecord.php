<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record;

use KDuma\SimpleDAL\Typed\Contracts\Attribute\Field;
use KDuma\SimpleDAL\Typed\Contracts\TypedRecord;

class CaStateRecord extends TypedRecord
{
    #[Field]
    public ?string $activeCaCertificateId;

    #[Field]
    public int $lastIssuedSequence;
}
