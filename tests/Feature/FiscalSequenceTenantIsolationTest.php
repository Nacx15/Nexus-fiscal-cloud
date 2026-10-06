<?php

namespace Tests\Feature;

use App\Domain\Fiscal\Enums\FiscalDocumentType;
use App\Models\Company;
use App\Models\FiscalSequence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FiscalSequenceTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Company $companyA;

    private Company $companyB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->companyA = Company::factory()->create();

        $this->companyB = Company::factory()->create();

        foreach ([
            $this->companyA,
            $this->companyB,
        ] as $company) {
            $this->user
                ->companies()
                ->attach(
                    $company->id,
                    [
                        'role' => 'owner',
                        'status' => 'active',
                        'joined_at' => now(),
                    ]
                );
        }

        Sanctum::actingAs(
            $this->user
        );
    }

    public function test_list_only_returns_sequences_from_current_company(): void
    {
        $sequenceA = FiscalSequence::factory()
            ->for($this->companyA)
            ->create([
                'document_type' =>
                    FiscalDocumentType::Invoice->value,

                'series' => 'A',
            ]);

        FiscalSequence::factory()
            ->for($this->companyB)
            ->create([
                'document_type' =>
                    FiscalDocumentType::Invoice->value,

                'series' => 'B',
            ]);

        $this
            ->withHeader(
                'X-Company-ID',
                (string) $this->companyA->id
            )
            ->getJson('/api/v1/fiscal-sequences')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.id',
                $sequenceA->id
            )
            ->assertJsonPath(
                'data.0.series',
                'A'
            );
    }

    public function test_sequence_from_other_company_cannot_be_viewed(): void
    {
        $sequenceB = FiscalSequence::factory()
            ->for($this->companyB)
            ->create([
                'series' => 'B',
            ]);

        $this
            ->withHeader(
                'X-Company-ID',
                (string) $this->companyA->id
            )
            ->getJson(
                "/api/v1/fiscal-sequences/{$sequenceB->id}"
            )
            ->assertNotFound();
    }

    public function test_same_series_can_exist_in_different_companies(): void
    {
        FiscalSequence::factory()
            ->for($this->companyB)
            ->create([
                'document_type' =>
                    FiscalDocumentType::Invoice->value,

                'series' => 'A',
            ]);

        $payload = [
            'document_type' => 'invoice',
            'series' => 'A',
            'next_number' => 1,
        ];

        $this
            ->withHeader(
                'X-Company-ID',
                (string) $this->companyA->id
            )
            ->postJson(
                '/api/v1/fiscal-sequences',
                $payload
            )
            ->assertCreated();

        $this->assertDatabaseHas(
            'fiscal_sequences',
            [
                'company_id' =>
                    $this->companyA->id,

                'document_type' =>
                    'invoice',

                'series' =>
                    'A',
            ]
        );

        $this->assertDatabaseHas(
            'fiscal_sequences',
            [
                'company_id' =>
                    $this->companyB->id,

                'document_type' =>
                    'invoice',

                'series' =>
                    'A',
            ]
        );
    }

    public function test_same_document_type_and_series_cannot_be_duplicated_inside_same_company(): void
    {
        FiscalSequence::factory()
            ->for($this->companyA)
            ->create([
                'document_type' =>
                    FiscalDocumentType::Invoice->value,

                'series' => 'A',
            ]);

        $payload = [
            'document_type' => 'invoice',
            'series' => 'A',
            'next_number' => 1,
        ];

        $this
            ->withHeader(
                'X-Company-ID',
                (string) $this->companyA->id
            )
            ->postJson(
                '/api/v1/fiscal-sequences',
                $payload
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'series',
            ]);
    }

    public function test_company_id_cannot_be_supplied_by_client(): void
    {
        $payload = [
            'company_id' =>
                $this->companyB->id,

            'document_type' =>
                'invoice',

            'series' =>
                'A',

            'next_number' =>
                1,
        ];

        $this
            ->withHeader(
                'X-Company-ID',
                (string) $this->companyA->id
            )
            ->postJson(
                '/api/v1/fiscal-sequences',
                $payload
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'company_id',
            ]);
    }
}
