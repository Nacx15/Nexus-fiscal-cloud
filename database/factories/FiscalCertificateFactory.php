<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\FiscalCertificate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FiscalCertificate>
 */
class FiscalCertificateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' =>
                Company::factory(),

            'company_fiscal_profile_id' =>
                null,

            'certificate_number' =>
                fake()->numerify(
                    '####################'
                ),

            'certificate_path' =>
                'testing/certificate.cer',

            'private_key_path' =>
                'testing/private.key',

            'private_key_password' =>
                'testing-secret',

            'fingerprint_sha256' =>
                hash(
                    'sha256',
                    fake()->uuid()
                ),

            'valid_from' =>
                now()->subMonth(),

            'valid_until' =>
                now()->addYear(),

            'status' =>
                'inactive',
        ];
    }
}
