<?php

declare(strict_types=1);

namespace KDuma\PhpCA\Record\Enum;

enum RevocationReason: string
{
    case Unspecified = 'unspecified';
    case KeyCompromise = 'keyCompromise';
    case CaCompromise = 'caCompromise';
    case AffiliationChanged = 'affiliationChanged';
    case Superseded = 'superseded';
    case CessationOfOperation = 'cessationOfOperation';
    case CertificateHold = 'certificateHold';
    case RemoveFromCRL = 'removeFromCRL';
    case PrivilegeWithdrawn = 'privilegeWithdrawn';
    case AaCompromise = 'aaCompromise';
}
