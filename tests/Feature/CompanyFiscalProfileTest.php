<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyFiscalProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CompanyFiscalProfileTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Company $companyA;

    private Company $companyB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user =
            User::factory()->create();

        $this->companyA =
            Company::factory()->create();

        $this->companyB =
            Company::factory()->create();

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

    public function test_company_can_create_fiscal_profile(): void
    {
        $payload = [
            'rfc' => 'ABC010101ABC',
            'legal_name' =>
                'EMPRESA DEMO SA DE CV',
            'tax_regime' => '601',
            'postal_code' => '97000',
            'email' => 'fiscal@example.com',
        ];

        $this
            ->withHeader(
                'X-Company-ID',
                (string) $this->companyA->id
            )
            ->postJson(
                '/api/v1/company/fiscal-profile',
                $payload
            )
            ->assertCreated()
            ->assertJsonPath(
                'data.rfc',
                'ABC010101ABC'
            );

        $this->assertDatabaseHas(
            'company_fiscal_profiles',
            [
                'company_id' =>
                    $this->companyA->id,

                'rfc' =>
                    'ABC010101ABC',
            ]
        );
    }

    public function test_company_cannot_create_second_fiscal_profile(): void
    {
        CompanyFiscalProfile::factory()
            ->for($this->companyA)
            ->create();

        $payload = [
            'rfc' => 'XYZ010101XYZ',
            'legal_name' =>
                'OTRA EMPRESA SA DE CV',
            'tax_regime' => '601',
            'postal_code' => '97000',
        ];

        $this
            ->withHeader(
                'X-Company-ID',
                (string) $this->companyA->id
            )
            ->postJson(
                '/api/v1/company/fiscal-profile',
                $payload
            )
            ->assertConflict()
            ->assertJson([
                'code' =>
                    'fiscal_profile_already_exists',
            ]);
    }

    public function test_show_only_returns_current_company_profile(): void
    {
        $profileA =
            CompanyFiscalProfile::factory()
                ->for($this->companyA)
                ->create([
                    'rfc' =>
                        'ABC010101ABC',
                ]);

        CompanyFiscalProfile::factory()
            ->for($this->companyB)
            ->create([
                'rfc' =>
                    'XYZ010101XYZ',
            ]);

        $this
            ->withHeader(
                'X-Company-ID',
                (string) $this->companyA->id
            )
            ->getJson(
                '/api/v1/company/fiscal-profile'
            )
            ->assertOk()
            ->assertJsonPath(
                'data.id',
                $profileA->id
            )
            ->assertJsonPath(
                'data.rfc',
                'ABC010101ABC'
            );
    }

    public function test_company_without_profile_gets_not_found(): void
    {
        $this
            ->withHeader(
                'X-Company-ID',
                (string) $this->companyA->id
            )
            ->getJson(
                '/api/v1/company/fiscal-profile'
            )
            ->assertNotFound();
    }

    public function test_company_can_update_its_fiscal_profile(): void
    {
        CompanyFiscalProfile::factory()
            ->for($this->companyA)
            ->create();

        $this
            ->withHeader(
                'X-Company-ID',
                (string) $this->companyA->id
            )
            ->patchJson(
                '/api/v1/company/fiscal-profile',
                [
                    'postal_code' =>
                        '97100',
                ]
            )
            ->assertOk()
            ->assertJsonPath(
                'data.postal_code',
                '97100'
            );

        $this->assertDatabaseHas(
            'company_fiscal_profiles',
            [
                'company_id' =>
                    $this->companyA->id,

                'postal_code' =>
                    '97100',
            ]
        );
    }

    public function test_company_id_cannot_be_supplied(): void
    {
        $payload = [
            'company_id' =>
                $this->companyB->id,

            'rfc' =>
                'ABC010101ABC',

            'legal_name' =>
                'EMPRESA DEMO SA DE CV',

            'tax_regime' =>
                '601',

            'postal_code' =>
                '97000',
        ];

        $this
            ->withHeader(
                'X-Company-ID',
                (string) $this->companyA->id
            )
            ->postJson(
                '/api/v1/company/fiscal-profile',
                $payload
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'company_id',
            ]);
    }
}
