<?php

namespace App\Application\Fiscal\Data;

use DateTimeImmutable;

final readonly class PacStampResult
{
    public function __construct(
        public string $uuid,
        public string $stampedXml,
        public DateTimeImmutable $stampedAt,
        public ?string $transactionId = null,
    ) {
    }
}
