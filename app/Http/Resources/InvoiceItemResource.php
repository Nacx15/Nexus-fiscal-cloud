<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' =>
                $this->id,

            'line_number' =>
                $this->line_number,

            'sat_product_code' =>
                $this->sat_product_code,

            'unit_code' =>
                $this->unit_code,

            'description' =>
                $this->description,

            'tax_object' =>
                $this->tax_object,

            'quantity' =>
                $this->quantity,

            'unit_price' =>
                $this->unit_price,

            'subtotal' =>
                $this->subtotal,

            'discount' =>
                $this->discount,

            'taxable_base' =>
                $this->taxable_base,

            'transferred_taxes' =>
                $this->transferred_taxes,

            'withheld_taxes' =>
                $this->withheld_taxes,

            'total' =>
                $this->total,

            'taxes' =>
                InvoiceItemTaxResource::collection(
                    $this->whenLoaded(
                        'taxes'
                    )
                ),
        ];
    }
}
