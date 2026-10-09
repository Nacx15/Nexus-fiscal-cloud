<?php

namespace App\Http\Requests\Api\V1;

use App\Domain\Fiscal\Enums\FiscalDocumentType;
use App\Tenancy\CompanyContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId =
            app(CompanyContext::class)->id();

        return [
            'company_id' => [
                'prohibited',
            ],

            'client_id' => [
                'required',
                'integer',

                Rule::exists(
                    'clients',
                    'id'
                )->where(
                    fn ($query) =>
                        $query
                            ->where(
                                'company_id',
                                $companyId
                            )
                            ->where(
                                'status',
                                'active'
                            )
                            ->whereNull(
                                'deleted_at'
                            )
                ),
            ],

            'fiscal_sequence_id' => [
                'required',
                'integer',

                Rule::exists(
                    'fiscal_sequences',
                    'id'
                )->where(
                    fn ($query) =>
                        $query
                            ->where(
                                'company_id',
                                $companyId
                            )
                            ->where(
                                'document_type',
                                FiscalDocumentType::Invoice->value
                            )
                            ->where(
                                'status',
                                'active'
                            )
                ),
            ],

            'payment_form' => [
                'required',
                'digits:2',
            ],

            'payment_method' => [
                'required',
                'string',
                'size:3',
            ],

            'cfdi_use' => [
                'required',
                'string',
                'size:3',
            ],

            /*
            |--------------------------------------------------------------------------
            | V1
            |--------------------------------------------------------------------------
            |
            | Por ahora Nexus emitirá únicamente MXN.
            |
            */

            'currency' => [
                'prohibited',
            ],

            'exchange_rate' => [
                'prohibited',
            ],

            'items' => [
                'required',
                'array',
                'min:1',
                'max:100',
            ],

            'items.*.product_id' => [
                'required',
                'integer',

                Rule::exists(
                    'products',
                    'id'
                )->where(
                    fn ($query) =>
                        $query
                            ->where(
                                'company_id',
                                $companyId
                            )
                            ->where(
                                'status',
                                'active'
                            )
                            ->whereNull(
                                'deleted_at'
                            )
                ),
            ],

            'items.*.quantity' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'items.*.discount' => [
                'sometimes',
                'numeric',
                'min:0',
            ],

            /*
            |--------------------------------------------------------------------------
            | No permitimos manipular estos snapshots/importes desde HTTP
            |--------------------------------------------------------------------------
            */

            'subtotal' => [
                'prohibited',
            ],

            'total' => [
                'prohibited',
            ],

            'series' => [
                'prohibited',
            ],

            'folio' => [
                'prohibited',
            ],

            'status' => [
                'prohibited',
            ],
        ];
    }
}
