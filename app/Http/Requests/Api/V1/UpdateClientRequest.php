<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use App\Tenancy\CompanyContext;
use Illuminate\Validation\Rule;

class UpdateClientRequest extends FormRequest
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
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $companyId = app(CompanyContext::class)->id();

        $clientId = (int) $this->route('client');

        return [
            'company_id' => [
                'prohibited',
            ],

            'tax_name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],

            'rfc' => [
                'sometimes',
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
                    )
                    ->ignore($clientId),
            ],

            'tax_regime' => [
                'sometimes',
                'required',
                'string',
                'size:3',
            ],

            'postal_code' => [
                'sometimes',
                'required',
                'digits:5',
            ],

            'email' => [
                'sometimes',
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
