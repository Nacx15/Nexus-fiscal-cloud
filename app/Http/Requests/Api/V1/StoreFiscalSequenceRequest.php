<?php

namespace App\Http\Requests\Api\V1;

use App\Domain\Fiscal\Enums\FiscalDocumentType;
use App\Tenancy\CompanyContext;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreFiscalSequenceRequest extends FormRequest
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
        $companyId =
            app(CompanyContext::class)->id();

        return [
            'company_id' => [
                'prohibited',
            ],

            'document_type' => [
                'required',

                Rule::enum(
                    FiscalDocumentType::class
                ),
            ],

            'series' => [
                'required',
                'string',
                'max:25',

                Rule::unique(
                    'fiscal_sequences',
                    'series'
                )->where(
                    fn ($query) =>
                        $query
                            ->where(
                                'company_id',
                                $companyId
                            )
                            ->where(
                                'document_type',
                                $this->input(
                                    'document_type'
                                )
                            )
                ),
            ],

            'next_number' => [
                'sometimes',
                'integer',
                'min:1',
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
