<?php

namespace App\Application\Fiscal\Data;

final readonly class CfdiBuildResult
{
    public function __construct(
        public string $xml,
        public string $originalString,
        public int $certificateId,
        public string $certificateNumber,
    ) {
    }
}
