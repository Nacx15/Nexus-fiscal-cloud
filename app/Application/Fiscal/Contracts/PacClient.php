<?php

namespace App\Application\Fiscal\Contracts;

use App\Application\Fiscal\Data\PacStampResult;

interface PacClient
{
    public function stamp(
        string $cfdiXml
    ): PacStampResult;
}
