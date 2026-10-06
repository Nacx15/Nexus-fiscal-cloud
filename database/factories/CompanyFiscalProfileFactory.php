<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\CompanyFiscalProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanyFiscalProfile>
 */
class CompanyFiscalProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),

            'rfc' => strtoupper(
                fake()->bothify('???######???')
            ),

            'legal_name' => strtoupper(
                fake()->company()
            ),

            'tax_regime' => '601',

            'postal_code' =>
                fake()->numerify('#####'),

            'email' => fake()->safeEmail(),

            'status' => 'active',
        ];
    }
}
