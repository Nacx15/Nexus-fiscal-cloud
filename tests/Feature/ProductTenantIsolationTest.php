<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductTenantIsolationTest extends TestCase
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

    public function test_list_only_returns_products_from_current_company(): void
    {
        $productA = Product::factory()
            ->for($this->companyA)
            ->create();

        Product::factory()
            ->for($this->companyB)
            ->create();

        $this
            ->withHeader(
                'X-Company-ID',
                (string) $this->companyA->id
            )
            ->getJson('/api/v1/products')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.id',
                $productA->id
            );
    }

    public function test_product_from_other_company_cannot_be_viewed(): void
    {
        $productB = Product::factory()
            ->for($this->companyB)
            ->create();

        $this
            ->withHeader(
                'X-Company-ID',
                (string) $this->companyA->id
            )
            ->getJson(
                "/api/v1/products/{$productB->id}"
            )
            ->assertNotFound();
    }

    public function test_product_is_created_inside_current_company(): void
    {
        $payload = [
            'sku' => 'PROD-001',
            'sat_product_code' => '01010101',
            'description' => 'Producto demo',
            'unit_code' => 'H87',
            'unit_price' => '100.000000',
            'tax_object' => '02',
            'default_tax_rate' => '0.160000',
        ];

        $this
            ->withHeader(
                'X-Company-ID',
                (string) $this->companyA->id
            )
            ->postJson(
                '/api/v1/products',
                $payload
            )
            ->assertCreated()
            ->assertJsonPath(
                'data.sku',
                'PROD-001'
            );

        $this->assertDatabaseHas(
            'products',
            [
                'company_id' =>
                    $this->companyA->id,

                'sku' =>
                    'PROD-001',
            ]
        );
    }

    public function test_same_sku_can_exist_in_different_companies(): void
    {
        Product::factory()
            ->for($this->companyB)
            ->create([
                'sku' => 'PROD-001',
            ]);

        $payload = [
            'sku' => 'PROD-001',
            'sat_product_code' => '01010101',
            'description' => 'Producto Company A',
            'unit_code' => 'H87',
            'unit_price' => '100.000000',
            'tax_object' => '02',
        ];

        $this
            ->withHeader(
                'X-Company-ID',
                (string) $this->companyA->id
            )
            ->postJson(
                '/api/v1/products',
                $payload
            )
            ->assertCreated();

        $this->assertDatabaseCount(
            'products',
            2
        );
    }

    public function test_same_sku_cannot_be_duplicated_inside_same_company(): void
    {
        Product::factory()
            ->for($this->companyA)
            ->create([
                'sku' => 'PROD-001',
            ]);

        $payload = [
            'sku' => 'PROD-001',
            'sat_product_code' => '01010101',
            'description' => 'Duplicado',
            'unit_code' => 'H87',
            'unit_price' => '100.000000',
            'tax_object' => '02',
        ];

        $this
            ->withHeader(
                'X-Company-ID',
                (string) $this->companyA->id
            )
            ->postJson(
                '/api/v1/products',
                $payload
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'sku',
            ]);
    }

    public function test_product_from_other_company_cannot_be_updated(): void
    {
        $productB = Product::factory()
            ->for($this->companyB)
            ->create();

        $this
            ->withHeader(
                'X-Company-ID',
                (string) $this->companyA->id
            )
            ->patchJson(
                "/api/v1/products/{$productB->id}",
                [
                    'description' =>
                        'Intento de modificación',
                ]
            )
            ->assertNotFound();
    }

    public function test_product_from_other_company_cannot_be_deleted(): void
    {
        $productB = Product::factory()
            ->for($this->companyB)
            ->create();

        $this
            ->withHeader(
                'X-Company-ID',
                (string) $this->companyA->id
            )
            ->deleteJson(
                "/api/v1/products/{$productB->id}"
            )
            ->assertNotFound();

        $this->assertDatabaseHas(
            'products',
            [
                'id' => $productB->id,
                'company_id' => $this->companyB->id,
                'deleted_at' => null,
            ]
        );
    }

    public function test_company_id_cannot_be_supplied_by_client(): void
    {
        $payload = [
            'company_id' => $this->companyB->id,
            'sku' => 'MALICIOUS-001',
            'sat_product_code' => '01010101',
            'description' => 'Producto malicioso',
            'unit_code' => 'H87',
            'unit_price' => '100.000000',
            'tax_object' => '02',
        ];

        $this
            ->withHeader(
                'X-Company-ID',
                (string) $this->companyA->id
            )
            ->postJson(
                '/api/v1/products',
                $payload
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'company_id',
            ]);
    }
}
