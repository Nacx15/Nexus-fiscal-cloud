<?php

namespace App\Domain\Fiscal\Enums;

enum FiscalCertificateStatus: string
{
    case Active = 'active';

    case Inactive = 'inactive';

    case Revoked = 'revoked';
}
