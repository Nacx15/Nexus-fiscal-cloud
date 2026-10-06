<?php

namespace App\Http\Requests\Api\V1;

use App\Tenancy\CompanyContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('rfc')) {
            $this->merge([
                'rfc' => strtoupper(
                    trim((string) $this->input('rfc'))
                ),
            ]);
        }
    }

    public function rules(): array
    {
        $companyId = app(CompanyContext::class)->id();

        return [
            'company_id' => [
                'prohibited',
            ],

            'tax_name' => [
                'required',
                'string',
                'max:255',
            ],

            'rfc' => [
                'required',
                'string',
                'min:12',
                'max:13',

                Rule::unique('clients', 'rfc')
                    ->where(
                        fn ($query) => $query->where(
                            'company_id',
                            $companyId
                        )
                    ),
            ],

            'tax_regime' => [
                'required',
                'string',
                'size:3',
            ],

            'postal_code' => [
                'required',
                'digits:5',
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
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
