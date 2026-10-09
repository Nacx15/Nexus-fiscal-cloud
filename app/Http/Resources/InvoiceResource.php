<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' =>
                $this->id,

            'series' =>
                $this->series,

            'folio' =>
                $this->folio,

            'internal_folio' =>
                $this->internal_folio,

            'uuid' =>
                $this->uuid,

            'status' =>
                $this->status->value,

            'issuer' => [
                'rfc' =>
                    $this->issuer_rfc,

                'name' =>
                    $this->issuer_name,

                'tax_regime' =>
                    $this
                        ->issuer_tax_regime,

                'postal_code' =>
                    $this
                        ->issuer_postal_code,
            ],

            'receiver' => [
                'rfc' =>
                    $this->receiver_rfc,

                'name' =>
                    $this->receiver_name,

                'tax_regime' =>
                    $this
                        ->receiver_tax_regime,

                'postal_code' =>
                    $this
                        ->receiver_postal_code,

                'email' =>
                    $this
                        ->receiver_email,
            ],

            'payment_form' =>
                $this->payment_form,

            'payment_method' =>
                $this->payment_method,

            'cfdi_use' =>
                $this->cfdi_use,

            'currency' =>
                $this->currency,

            'subtotal' =>
                $this->subtotal,

            'discount' =>
                $this->discount,

            'transferred_taxes' =>
                $this->transferred_taxes,

            'withheld_taxes' =>
                $this->withheld_taxes,

            'total' =>
                $this->total,

            'items' =>
                InvoiceItemResource::collection(
                    $this->whenLoaded(
                        'items'
                    )
                ),

            'created_at' =>
                $this->created_at,
        ];
    }
}
