<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ClientTenantIsolationTest extends TestCase
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

        $this->user->companies()->attach(
            $this->companyA->id,
            [
                'role' => 'owner',
                'status' => 'active',
                'joined_at' => now(),
            ]
        );

        $this->user->companies()->attach(
            $this->companyB->id,
            [
                'role' => 'owner',
                'status' => 'active',
                'joined_at' => now(),
            ]
        );

        Sanctum::actingAs($this->user);
    }

    public function test_list_only_returns_clients_from_current_company(): void
    {
        $clientA = Client::factory()
            ->for($this->companyA)
            ->create();

        Client::factory()
            ->for($this->companyB)
            ->create();

        $response = $this
            ->withHeader(
                'X-Company-ID',
                (string) $this->companyA->id
            )
            ->getJson('/api/v1/clients');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.id',
                $clientA->id
            );
    }

    public function test_client_from_other_company_cannot_be_viewed(): void
    {
        $clientB = Client::factory()
            ->for($this->companyB)
            ->create();

        $this
            ->withHeader(
                'X-Company-ID',
                (string) $this->companyA->id
            )
            ->getJson(
                "/api/v1/clients/{$clientB->id}"
            )
            ->assertNotFound();
    }

    public function test_client_is_created_inside_current_company(): void
    {
        $payload = [
            'tax_name' => 'CLIENTE DEMO SA DE CV',
            'rfc' => 'ABC010101ABC',
            'tax_regime' => '601',
            'postal_code' => '97000',
            'email' => 'cliente@example.com',
        ];

        $response = $this
            ->withHeader(
                'X-Company-ID',
                (string) $this->companyA->id
            )
            ->postJson(
                '/api/v1/clients',
                $payload
            );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'data.rfc',
                'ABC010101ABC'
            );

        $this->assertDatabaseHas(
            'clients',
            [
                'company_id' => $this->companyA->id,
                'rfc' => 'ABC010101ABC',
            ]
        );
    }

    public function test_same_rfc_can_exist_in_different_companies(): void
    {
        Client::factory()
            ->for($this->companyB)
            ->create([
                'rfc' => 'ABC010101ABC',
            ]);

        $payload = [
            'tax_name' => 'CLIENTE EMPRESA A',
            'rfc' => 'ABC010101ABC',
            'tax_regime' => '601',
            'postal_code' => '97000',
        ];

        $this
            ->withHeader(
                'X-Company-ID',
                (string) $this->companyA->id
            )
            ->postJson(
                '/api/v1/clients',
                $payload
            )
            ->assertCreated();

        $this->assertDatabaseCount(
            'clients',
            2
        );
    }

    public function test_same_rfc_cannot_be_duplicated_inside_same_company(): void
    {
        Client::factory()
            ->for($this->companyA)
            ->create([
                'rfc' => 'ABC010101ABC',
            ]);

        $payload = [
            'tax_name' => 'OTRO CLIENTE',
            'rfc' => 'ABC010101ABC',
            'tax_regime' => '601',
            'postal_code' => '97000',
        ];

        $this
            ->withHeader(
                'X-Company-ID',
                (string) $this->companyA->id
            )
            ->postJson(
                '/api/v1/clients',
                $payload
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'rfc',
            ]);
    }

    public function test_client_from_other_company_cannot_be_updated(): void
    {
        $clientB = Client::factory()
            ->for($this->companyB)
            ->create();

        $this
            ->withHeader(
                'X-Company-ID',
                (string) $this->companyA->id
            )
            ->patchJson(
                "/api/v1/clients/{$clientB->id}",
                [
                    'tax_name' => 'MODIFICADO',
                ]
            )
            ->assertNotFound();
    }

    public function test_client_from_other_company_cannot_be_deleted(): void
    {
        $clientB = Client::factory()
            ->for($this->companyB)
            ->create();

        $this
            ->withHeader(
                'X-Company-ID',
                (string) $this->companyA->id
            )
            ->deleteJson(
                "/api/v1/clients/{$clientB->id}"
            )
            ->assertNotFound();

        $this->assertDatabaseHas(
            'clients',
            [
                'id' => $clientB->id,
                'company_id' => $this->companyB->id,
                'deleted_at' => null,
            ]
        );
    }

    public function test_company_id_cannot_be_supplied_by_client(): void
    {
        $payload = [
            'company_id' => $this->companyB->id,
            'tax_name' => 'CLIENTE MALICIOSO',
            'rfc' => 'XYZ010101XYZ',
            'tax_regime' => '601',
            'postal_code' => '97000',
        ];

        $this
            ->withHeader(
                'X-Company-ID',
                (string) $this->companyA->id
            )
            ->postJson(
                '/api/v1/clients',
                $payload
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'company_id',
            ]);
    }
}
