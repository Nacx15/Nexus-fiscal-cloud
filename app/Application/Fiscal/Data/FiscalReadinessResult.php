<?php

namespace App\Application\Fiscal\Data;

final readonly class FiscalReadinessResult
{
    public function __construct(
        public bool $ready,
        public array $checks,
    ) {
    }
}
