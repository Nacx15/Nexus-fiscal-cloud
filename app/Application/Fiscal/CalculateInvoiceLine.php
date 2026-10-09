<?php

namespace App\Application\Fiscal;

use App\Application\Fiscal\Data\InvoiceLineCalculation;
use App\Models\Product;
use InvalidArgumentException;

final class CalculateInvoiceLine
{
    private const SCALE = 6;

    public function execute(
        Product $product,
        string $quantity,
        string $discount = '0'
    ): InvoiceLineCalculation {
        $quantity =
            $this->decimal($quantity);

        $unitPrice =
            $this->decimal(
                (string) $product->unit_price
            );

        $discount =
            $this->decimal($discount);

        $subtotal = bcmul(
            $quantity,
            $unitPrice,
            self::SCALE
        );

        if (
            bccomp(
                $discount,
                $subtotal,
                self::SCALE
            ) === 1
        ) {
            throw new InvalidArgumentException(
                'Discount cannot exceed line subtotal.'
            );
        }

        $taxableBase = bcsub(
            $subtotal,
            $discount,
            self::SCALE
        );

        $transferredTaxes =
            '0.000000';

        $withheldTaxes =
            '0.000000';

        $taxes = [];

        /*
        |--------------------------------------------------------------------------
        | V1: IVA trasladado
        |--------------------------------------------------------------------------
        */

        if (
            $product->tax_object === '02' &&
            $product->default_tax_rate !== null
        ) {
            $rate =
                $this->decimal(
                    (string)
                    $product->default_tax_rate
                );

            $transferredTaxes =
                bcmul(
                    $taxableBase,
                    $rate,
                    self::SCALE
                );

            $taxes[] = [
                'tax_code' => '002',

                'tax_type' =>
                    'transferred',

                'factor_type' =>
                    'Tasa',

                'base' =>
                    $taxableBase,

                'rate_or_quota' =>
                    $rate,

                'amount' =>
                    $transferredTaxes,
            ];
        }

        $total = bcadd(
            $taxableBase,
            $transferredTaxes,
            self::SCALE
        );

        $total = bcsub(
            $total,
            $withheldTaxes,
            self::SCALE
        );

        return new InvoiceLineCalculation(
            subtotal:
                $subtotal,

            discount:
                $discount,

            taxableBase:
                $taxableBase,

            transferredTaxes:
                $transferredTaxes,

            withheldTaxes:
                $withheldTaxes,

            total:
                $total,

            taxes:
                $taxes,
        );
    }

    private function decimal(
        string $value
    ): string {
        return bcadd(
            $value,
            '0',
            self::SCALE
        );
    }
}
