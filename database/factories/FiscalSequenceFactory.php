<?php

namespace Database\Factories;

use App\Domain\Fiscal\Enums\FiscalDocumentType;
use App\Models\Company;
use App\Models\FiscalSequence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FiscalSequence>
 */
class FiscalSequenceFactory extends Factory
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

            'document_type' =>
                FiscalDocumentType::Invoice->value,

            'series' =>
                'A',

            'next_number' =>
                1,

            'status' =>
                'active',
        ];
    }
}
