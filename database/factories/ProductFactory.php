<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Company;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
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

            'sku' => fake()
                ->unique()
                ->bothify('SKU-####'),

            'sat_product_code' => '01010101',

            'description' => fake()->sentence(),

            'unit_code' => 'H87',

            'unit_price' => fake()->randomFloat(
                2,
                10,
                5000
            ),

            'tax_object' => '02',

            'default_tax_rate' => '0.160000',

            'status' => 'active',
        ];
    }
}
