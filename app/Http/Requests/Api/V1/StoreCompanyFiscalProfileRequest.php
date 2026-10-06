<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyFiscalProfileRequest extends FormRequest
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
        if ($this->filled('rfc')) {
            $this->merge([
                'rfc' => strtoupper(
                    trim((string) $this->input('rfc'))
                ),
            ]);
        }

        if ($this->filled('legal_name')) {
            $this->merge([
                'legal_name' => strtoupper(
                    trim(
                        (string) $this->input(
                            'legal_name'
                        )
                    )
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
        return [
            'company_id' => [
                'prohibited',
            ],

            'rfc' => [
                'required',
                'string',
                'min:12',
                'max:13',
                'regex:/^[A-ZÑ&]{3,4}[0-9]{6}[A-Z0-9]{3}$/',
            ],

            'legal_name' => [
                'required',
                'string',
                'max:255',
            ],

            'tax_regime' => [
                'required',
                'digits:3',
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
