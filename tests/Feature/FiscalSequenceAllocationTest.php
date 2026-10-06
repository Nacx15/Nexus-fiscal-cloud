<?php

namespace Tests\Feature;

use App\Application\Fiscal\AllocateFiscalFolio;
use App\Domain\Fiscal\Enums\FiscalDocumentType;
use App\Models\Company;
use App\Models\FiscalSequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class FiscalSequenceAllocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_folios_are_allocated_sequentially(): void
    {
        $company =
            Company::factory()->create();

        $sequence =
            FiscalSequence::factory()
                ->for($company)
                ->create([
                    'next_number' => 100,
                ]);

        $allocator =
            app(AllocateFiscalFolio::class);

        $first = $allocator->execute(
            $sequence->id,
            $company->id
        );

        $second = $allocator->execute(
            $sequence->id,
            $company->id
        );

        $third = $allocator->execute(
            $sequence->id,
            $company->id
        );

        $this->assertSame(
            100,
            $first
        );

        $this->assertSame(
            101,
            $second
        );

        $this->assertSame(
            102,
            $third
        );

        $this->assertDatabaseHas(
            'fiscal_sequences',
            [
                'id' => $sequence->id,
                'next_number' => 103,
            ]
        );
    }

    public function test_companies_have_independent_sequences(): void
    {
        $companyA =
            Company::factory()->create();

        $companyB =
            Company::factory()->create();

        $sequenceA =
            FiscalSequence::factory()
                ->for($companyA)
                ->create([
                    'series' => 'A',
                    'next_number' => 1,
                ]);

        $sequenceB =
            FiscalSequence::factory()
                ->for($companyB)
                ->create([
                    'series' => 'A',
                    'next_number' => 1,
                ]);

        $allocator =
            app(AllocateFiscalFolio::class);

        $folioA =
            $allocator->execute(
                $sequenceA->id,
                $companyA->id
            );

        $folioB =
            $allocator->execute(
                $sequenceB->id,
                $companyB->id
            );

        $this->assertSame(1, $folioA);
        $this->assertSame(1, $folioB);
    }

    public function test_sequence_from_other_company_cannot_be_used(): void
    {
        $companyA =
            Company::factory()->create();

        $companyB =
            Company::factory()->create();

        $sequenceB =
            FiscalSequence::factory()
                ->for($companyB)
                ->create();

        $allocator =
            app(AllocateFiscalFolio::class);

        $this->expectException(
            \Illuminate\Database\Eloquent\ModelNotFoundException::class
        );

        $allocator->execute(
            $sequenceB->id,
            $companyA->id
        );
    }

    public function test_inactive_sequence_cannot_allocate_folio(): void
    {
        $company =
            Company::factory()->create();

        $sequence =
            FiscalSequence::factory()
                ->for($company)
                ->create([
                    'status' => 'inactive',
                ]);

        $allocator =
            app(AllocateFiscalFolio::class);

        $this->expectException(
            RuntimeException::class
        );

        $allocator->execute(
            $sequence->id,
            $company->id
        );
    }
}
