<?php

namespace App\Http\Requests\Api\V1;

use App\Tenancy\CompanyContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('sku')) {
            $this->merge([
                'sku' => strtoupper(
                    trim((string) $this->input('sku'))
                ),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $companyId = app(
            CompanyContext::class
        )->id();

        $productId = (int) $this->route(
            'product'
        );

        return [
            'company_id' => [
                'prohibited',
            ],

            'sku' => [
                'sometimes',
                'required',
                'string',
                'max:64',

                Rule::unique(
                    'products',
                    'sku'
                )
                    ->where(
                        fn ($query) => $query->where(
                            'company_id',
                            $companyId
                        )
                    )
                    ->ignore($productId),
            ],

            'sat_product_code' => [
                'sometimes',
                'required',
                'digits:8',
            ],

            'description' => [
                'sometimes',
                'required',
                'string',
                'max:1000',
            ],

            'unit_code' => [
                'sometimes',
                'required',
                'string',
                'max:3',
            ],

            'unit_price' => [
                'sometimes',
                'required',
                'numeric',
                'min:0',
            ],

            'tax_object' => [
                'sometimes',
                'required',
                'string',
                'size:2',
            ],

            'default_tax_rate' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
            ],

            'status' => [
                'sometimes',
                Rule::in([
                    'active',
                    'inactive',
                ]),
            ],
        ];
    }
}
