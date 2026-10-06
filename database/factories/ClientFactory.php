<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
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

            'tax_name' => fake()->company(),

            'rfc' => strtoupper(
                fake()->unique()->bothify('???######???')
            ),

            'tax_regime' => '601',

            'postal_code' => fake()->numerify('#####'),

            'email' => fake()->safeEmail(),

            'status' => 'active',
        ];
    }
}
