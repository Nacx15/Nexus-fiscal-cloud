<?php

namespace App\Application\Fiscal\Data;

final readonly class InvoiceLineCalculation
{
    public function __construct(
        public string $subtotal,
        public string $discount,
        public string $taxableBase,
        public string $transferredTaxes,
        public string $withheldTaxes,
        public string $total,
        public array $taxes,
    ) {
    }
}
