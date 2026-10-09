<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\WithFaker;
use App\Domain\Fiscal\Enums\FiscalCertificateStatus;
use App\Domain\Fiscal\Enums\FiscalDocumentType;
use App\Models\Client;
use App\Models\Company;
use App\Models\CompanyFiscalProfile;
use App\Models\FiscalCertificate;
use App\Models\FiscalSequence;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InvoiceCreationTest extends TestCase
{
	    use RefreshDatabase;

	    private User $user;

	    private Company $company;

	    private FiscalSequence $sequence;

	    private Client $client;

	    private Product $product;

    	protected function setUp(): void
	{
		parent::setUp();

		$this->user =
		    User::factory()->create();

		$this->company =
		    Company::factory()->create();

		$this->user
		    ->companies()
		    ->attach(
		        $this->company->id,
		        [
		            'role' => 'owner',
		            'status' => 'active',
		            'joined_at' => now(),
		        ]
		    );

		CompanyFiscalProfile::factory()
		    ->for($this->company)
		    ->create([
		        'status' => 'active',
		    ]);

		FiscalCertificate::factory()
		    ->for($this->company)
		    ->create([
		        'certificate_number' =>
		            '30001000000500003416',

		        'fingerprint_sha256' =>
		            str_repeat('a', 64),

		        'valid_from' =>
		            now()->subDay(),

		        'valid_until' =>
		            now()->addYear(),

		        'status' =>
		            FiscalCertificateStatus::Active->value,
		    ]);

		$this->sequence =
		    FiscalSequence::factory()
		        ->for($this->company)
		        ->create([
		            'document_type' =>
		                FiscalDocumentType::Invoice->value,

		            'series' => 'A',

		            'next_number' => 100,

		            'status' => 'active',
		        ]);

		$this->client =
		    Client::factory()
		        ->for($this->company)
		        ->create([
		            'tax_name' =>
		                'CLIENTE ORIGINAL SA DE CV',

		            'rfc' =>
		                'ABC010101ABC',
		        ]);

		$this->product =
		    Product::factory()
		        ->for($this->company)
		        ->create([
		            'description' =>
		                'Producto original',

		            'unit_price' =>
		                '100.000000',

		            'tax_object' =>
		                '02',

		            'default_tax_rate' =>
		                '0.160000',
		        ]);

		Sanctum::actingAs(
		    $this->user
		);
	}


	public function test_company_can_create_invoice_with_calculated_totals(): void
	{
	    $payload = [
	        'client_id' =>
	            $this->client->id,

	        'fiscal_sequence_id' =>
	            $this->sequence->id,

	        'payment_form' =>
	            '03',

	        'payment_method' =>
	            'PUE',

	        'cfdi_use' =>
	            'G03',

	        'items' => [
	            [
	                'product_id' =>
	                    $this->product->id,

	                'quantity' =>
	                    '2.000000',

	                'discount' =>
	                    '0.000000',
	            ],
	        ],
	    ];

	    $this
	        ->withHeader(
	            'X-Company-ID',
	            (string) $this->company->id
	        )
	        ->postJson(
	            '/api/v1/invoices',
	            $payload
	        )
	        ->assertCreated()
	        ->assertJsonPath(
	            'data.series',
	            'A'
	        )
	        ->assertJsonPath(
	            'data.folio',
	            100
	        )
	        ->assertJsonPath(
	            'data.internal_folio',
	            'A-100'
	        )
	        ->assertJsonPath(
	            'data.status',
	            'ready'
	        )
	        ->assertJsonPath(
	            'data.subtotal',
	            '200.000000'
	        )
	        ->assertJsonPath(
	            'data.transferred_taxes',
	            '32.000000'
	        )
	        ->assertJsonPath(
	            'data.total',
	            '232.000000'
	        );
	}


	public function test_invoice_snapshots_do_not_change_when_master_data_changes(): void
	{
	    $response = $this
	        ->withHeader(
	            'X-Company-ID',
	            (string) $this->company->id
	        )
	        ->postJson(
	            '/api/v1/invoices',
	            [
	                'client_id' =>
	                    $this->client->id,

	                'fiscal_sequence_id' =>
	                    $this->sequence->id,

	                'payment_form' =>
	                    '03',

	                'payment_method' =>
	                    'PUE',

	                'cfdi_use' =>
	                    'G03',

	                'items' => [
	                    [
	                        'product_id' =>
	                            $this->product->id,

	                        'quantity' =>
	                            '1.000000',
	                    ],
	                ],
	            ]
	        )
	        ->assertCreated();

	    $invoiceId =
	        $response->json('data.id');

	    /*
	    |--------------------------------------------------------------------------
	    | Cambiamos datos maestros
	    |--------------------------------------------------------------------------
	    */

	    $this->client->update([
	        'tax_name' =>
	            'CLIENTE MODIFICADO SA DE CV',
	    ]);

	    $this->product->update([
	        'description' =>
	            'Producto modificado',
	    ]);

	    /*
	    |--------------------------------------------------------------------------
	    | La factura debe conservar la historia
	    |--------------------------------------------------------------------------
	    */

	    $this
	        ->withHeader(
	            'X-Company-ID',
	            (string) $this->company->id
	        )
	        ->getJson(
	            "/api/v1/invoices/{$invoiceId}"
	        )
	        ->assertOk()
	        ->assertJsonPath(
	            'data.receiver.name',
	            'CLIENTE ORIGINAL SA DE CV'
	        )
	        ->assertJsonPath(
	            'data.items.0.description',
	            'Producto original'
	        );
	}


	public function test_invoice_folios_are_allocated_sequentially(): void
	{
	    $payload = [
	        'client_id' =>
	            $this->client->id,

	        'fiscal_sequence_id' =>
	            $this->sequence->id,

	        'payment_form' =>
	            '03',

	        'payment_method' =>
	            'PUE',

	        'cfdi_use' =>
	            'G03',

	        'items' => [
	            [
	                'product_id' =>
	                    $this->product->id,

	                'quantity' =>
	                    '1',
	            ],
	        ],
	    ];

	    $first = $this
	        ->withHeader(
	            'X-Company-ID',
	            (string) $this->company->id
	        )
	        ->postJson(
	            '/api/v1/invoices',
	            $payload
	        );

	    $second = $this
	        ->withHeader(
	            'X-Company-ID',
	            (string) $this->company->id
	        )
	        ->postJson(
	            '/api/v1/invoices',
	            $payload
	        );

	    $first
	        ->assertCreated()
	        ->assertJsonPath(
	            'data.folio',
	            100
	        );

	    $second
	        ->assertCreated()
	        ->assertJsonPath(
	            'data.folio',
	            101
	        );

	    $this->assertDatabaseHas(
	        'fiscal_sequences',
	        [
	            'id' =>
	                $this->sequence->id,

	            'next_number' =>
	                102,
	        ]
	    );
	}

	public function test_product_from_other_company_cannot_be_used(): void
	{
	    $otherCompany =
	        Company::factory()->create();

	    $otherProduct =
	        Product::factory()
	            ->for($otherCompany)
	            ->create();

	    $this
	        ->withHeader(
	            'X-Company-ID',
	            (string) $this->company->id
	        )
	        ->postJson(
	            '/api/v1/invoices',
	            [
	                'client_id' =>
	                    $this->client->id,

	                'fiscal_sequence_id' =>
	                    $this->sequence->id,

	                'payment_form' =>
	                    '03',

	                'payment_method' =>
	                    'PUE',

	                'cfdi_use' =>
	                    'G03',

	                'items' => [
	                    [
	                        'product_id' =>
	                            $otherProduct->id,

	                        'quantity' =>
	                            '1',
	                    ],
	                ],
	            ]
	        )
	        ->assertUnprocessable()
	        ->assertJsonValidationErrors([
	            'items.0.product_id',
	        ]);
	}

}
