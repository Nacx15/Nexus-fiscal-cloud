<?php

namespace App\Domain\Fiscal\Exceptions;

use RuntimeException;

final class CompanyNotFiscalReady extends RuntimeException
{
    public function __construct(
        public readonly array $checks
    ) {
        parent::__construct(
            'The company is not fiscally ready.'
        );
    }
}
