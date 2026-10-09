<?php

namespace App\Application\Fiscal\Data;

use DateTimeImmutable;

final readonly class FiscalCredentialMetadata
{
    public function __construct(
        public string $certificateNumber,
        public string $rfc,
        public string $legalName,
        public DateTimeImmutable $validFrom,
        public DateTimeImmutable $validUntil,
        public string $fingerprintSha256,
        public bool $currentlyValid,
    ) {
    }
}
