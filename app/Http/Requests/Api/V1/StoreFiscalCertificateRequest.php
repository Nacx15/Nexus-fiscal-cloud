<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class StoreFiscalCertificateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
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

            'certificate_file' => [
                'required',
                'file',
                'max:512',
                function (
                    string $attribute,
                    mixed $value,
                    \Closure $fail
                ): void {
                    if (
                        !$value instanceof UploadedFile ||
                        strtolower(
                            $value->getClientOriginalExtension()
                        ) !== 'cer'
                    ) {
                        $fail(
                            'The certificate file must have a .cer extension.'
                        );
                    }
                },
            ],

            'private_key_file' => [
                'required',
                'file',
                'max:512',
                function (
                    string $attribute,
                    mixed $value,
                    \Closure $fail
                ): void {
                    if (
                        !$value instanceof UploadedFile ||
                        strtolower(
                            $value->getClientOriginalExtension()
                        ) !== 'key'
                    ) {
                        $fail(
                            'The private key file must have a .key extension.'
                        );
                    }
                },
            ],

            'private_key_password' => [
                'required',
                'string',
                'max:1024',
            ],
        ];
    }
}
