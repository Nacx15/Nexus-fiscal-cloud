<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyFiscalProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'rfc' => $this->rfc,

            'legal_name' =>
                $this->legal_name,

            'tax_regime' =>
                $this->tax_regime,

            'postal_code' =>
                $this->postal_code,

            'email' =>
                $this->email,

            'status' =>
                $this->status,

            'created_at' =>
                $this->created_at,

            'updated_at' =>
                $this->updated_at,
        ];
    }
}
