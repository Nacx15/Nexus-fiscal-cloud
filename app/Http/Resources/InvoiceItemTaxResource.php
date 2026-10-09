<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceItemTaxResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'tax_code' =>
                $this->tax_code,

            'tax_type' =>
                $this->tax_type,

            'factor_type' =>
                $this->factor_type,

            'base' =>
                $this->base,

            'rate_or_quota' =>
                $this->rate_or_quota,

            'amount' =>
                $this->amount,
        ];
    }
}
