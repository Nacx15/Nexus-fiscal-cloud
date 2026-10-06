<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Validation\Rule;
use App\Tenancy\CompanyContext;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
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

        return [
            'company_id' => [
                'prohibited',
            ],

            'sku' => [
                'required',
                'string',
                'max:64',

                Rule::unique(
                    'products',
                    'sku'
                )->where(
                    fn ($query) => $query->where(
                        'company_id',
                        $companyId
                    )
                ),
            ],

            'sat_product_code' => [
                'required',
                'digits:8',
            ],

            'description' => [
                'required',
                'string',
                'max:1000',
            ],

            'unit_code' => [
                'required',
                'string',
                'max:3',
            ],

            'unit_price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'tax_object' => [
                'required',
                'string',
                'size:2',
            ],

            'default_tax_rate' => [
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
