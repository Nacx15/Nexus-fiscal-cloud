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
	    $prefix =
	        strtoupper(
	            fake()->lexify('???')
	        );

	    $date =
	        fake()->date('ymd');

	    $homoclave =
	        strtoupper(
	            fake()->bothify('??')
	        );

	    $checkDigit =
	        fake()->randomElement([
	            '0',
	            '1',
	            '2',
	            '3',
	            '4',
	            '5',
	            '6',
	            '7',
	            '8',
	            '9',
	            'A',
	        ]);

	    return [
	        'company_id' =>
	            Company::factory(),

	        'tax_name' =>
	            fake()->company(),

	        'rfc' =>
	            $prefix
	            . $date
	            . $homoclave
	            . $checkDigit,

	        'tax_regime' =>
	            '601',

	        'postal_code' =>
	            fake()->numerify('#####'),

	        'email' =>
	            fake()->safeEmail(),

	        'status' =>
	            'active',
	    ];
	}
}
