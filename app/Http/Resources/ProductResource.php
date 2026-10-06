<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'sku' => $this->sku,

            'sat_product_code' =>
                $this->sat_product_code,

            'description' =>
                $this->description,

            'unit_code' =>
                $this->unit_code,

            'unit_price' =>
                $this->unit_price,

            'tax_object' =>
                $this->tax_object,

            'default_tax_rate' =>
                $this->default_tax_rate,

            'status' =>
                $this->status,

            'created_at' =>
                $this->created_at,

            'updated_at' =>
                $this->updated_at,
        ];
    }
}
