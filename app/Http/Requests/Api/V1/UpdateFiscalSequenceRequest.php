<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateFiscalSequenceRequest extends FormRequest
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
        if ($this->filled('series')) {
            $this->merge([
                'series' => strtoupper(
                    trim(
                        (string) $this->input(
                            'series'
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

            'document_type' => [
                'prohibited',
            ],

            'next_number' => [
                'prohibited',
            ],

            'series' => [
                'prohibited',
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
