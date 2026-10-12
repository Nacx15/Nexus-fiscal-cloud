<?php

namespace Tests\Feature;

use App\Application\Fiscal\BuildInvoiceCfdiXml;
use App\Domain\Fiscal\Enums\FiscalCertificateStatus;
use App\Domain\Fiscal\Enums\FiscalDocumentType;
use App\Models\Client;
use App\Models\Company;
use App\Models\CompanyFiscalProfile;
use App\Models\FiscalCertificate;
use App\Models\FiscalSequence;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;
use CfdiUtils\Cfdi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use App\Domain\Fiscal\Exceptions\CfdiBuildException;

class BuildInvoiceCfdiXmlTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Company $company;

    private FiscalSequence $sequence;

    private Client $client;

    private Product $product;

    private FiscalCertificate $certificate;

	protected function setUp(): void
	{
	    parent::setUp();

	    Storage::fake(
	        'fiscal_certificates'
	    );

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
		        'rfc' =>
		            'EKU9003173C9',

		        'legal_name' =>
		            'ESCUELA KEMPER URGATE',

		        'tax_regime' =>
		            '601',

		        'postal_code' =>
		            '26015',

		        'status' =>
		            'active',
		    ]);

	    $this->sequence =
	        FiscalSequence::factory()
	            ->for($this->company)
	            ->create([
	                'document_type' =>
	                    FiscalDocumentType::Invoice
	                        ->value,

	                'series' =>
	                    'A',

	                'next_number' =>
	                    100,

	                'status' =>
	                    'active',
	            ]);

	    $this->client =
		    Client::factory()
		        ->for($this->company)
		        ->create([
		            'tax_name' =>
		                'CLIENTE DEMO SA DE CV',

		            'rfc' =>
		                'ABC010101AB0',

		            'tax_regime' =>
		                '601',

		            'postal_code' =>
		                '97000',

		            'status' =>
		                'active',
		        ]);

	    $this->product =
	        Product::factory()
	            ->for($this->company)
	            ->create([
	                'sat_product_code' =>
	                    '01010101',

	                'description' =>
	                    'Producto de prueba',

	                'unit_code' =>
	                    'H87',

	                'unit_price' =>
	                    '100.000000',

	                'tax_object' =>
	                    '02',

	                'default_tax_rate' =>
	                    '0.160000',

	                'status' =>
	                    'active',
	            ]);

	    $cer =
	        file_get_contents(
	            $this->csdPath(
	                'EKU9003173C9.cer'
	            )
	        );

	    $key =
	        file_get_contents(
	            $this->csdPath(
	                'EKU9003173C9.key'
	            )
	        );

	    $this->assertNotFalse($cer);
	    $this->assertNotFalse($key);

	    Storage::disk(
	        'fiscal_certificates'
	    )->put(
	        'company-'
	        . $this->company->id
	        . '/test.cer',
	        $cer
	    );

	    Storage::disk(
	        'fiscal_certificates'
	    )->put(
	        'company-'
	        . $this->company->id
	        . '/test.key',
	        $key
	    );

	    $this->certificate =
	        FiscalCertificate::factory()
	            ->for($this->company)
	            ->create([
	                'certificate_path' =>
	                    'company-'
	                    . $this->company->id
	                    . '/test.cer',

	                'private_key_path' =>
	                    'company-'
	                    . $this->company->id
	                    . '/test.key',

	                'private_key_password' =>
	                    $this->csdPassword(),

	                'valid_from' =>
	                    now()->subDay(),

	                'valid_until' =>
	                    now()->addYear(),

	                'status' =>
	                    FiscalCertificateStatus::Active
	                        ->value,
	            ]);

	    Sanctum::actingAs(
	        $this->user
	    );
	}

	private function csdPath(
	    string $filename
	): string {
	    return base_path(
	        'tests/Fixtures/csd/'
	        . $filename
	    );
	}

	private function csdPassword(): string
	{
	    $password =
	        file_get_contents(
	            $this->csdPath(
	                'EKU9003173C9.password.txt'
	            )
	        );

	    $this->assertNotFalse(
	        $password
	    );

	    return trim($password);
	}

	private function createInvoice(): Invoice
	{
	    $response =
	        $this
	            ->withHeader(
	                'X-Company-ID',
	                (string)
	                $this->company->id
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
	                                $this
	                                    ->product
	                                    ->id,

	                            'quantity' =>
	                                '2.000000',
	                        ],
	                    ],
	                ]
	            );

	    $response->assertCreated();

	    $invoice =
	        Invoice::query()
	            ->with(
	                'items.taxes'
	            )
	            ->findOrFail(
	                $response->json(
	                    'data.id'
	                )
	            );

	    /*
	    |--------------------------------------------------------------------------
	    | Simulamos inicio del proceso de timbrado
	    |--------------------------------------------------------------------------
	    */

	    $invoice->markStamping();

	    $invoice->save();

	    return $invoice->refresh();
	}

	public function test_invoice_can_be_built_as_signed_cfdi_40_xml(): void
	{
	    $invoice =
	        $this->createInvoice();

	    $builder =
	        app(
	            BuildInvoiceCfdiXml::class
	        );

	    $result = $builder->execute(
		    $invoice,
		    $this->certificate
		);

	    $this->assertNotEmpty(
	        $result->xml
	    );

	    $this->assertNotEmpty(
	        $result->originalString
	    );

	    $this->assertSame(
	        $this->certificate->id,
	        $result->certificateId
	    );

	    /*
	    |--------------------------------------------------------------------------
	    | Leer nuevamente el XML
	    |--------------------------------------------------------------------------
	    */

	    $cfdi =
	        Cfdi::newFromString(
	            $result->xml
	        );

	    $this->assertSame(
	        '4.0',
	        $cfdi->getVersion()
	    );

	    $comprobante =
	        $cfdi->getNode();

	    $this->assertSame(
	        'A',
	        (string)
	        $comprobante['Serie']
	    );

	    $this->assertSame(
	        '100',
	        (string)
	        $comprobante['Folio']
	    );

	    $this->assertSame(
	        'I',
	        (string)
	        $comprobante[
	            'TipoDeComprobante'
	        ]
	    );

	    $this->assertSame(
	        '01',
	        (string)
	        $comprobante[
	            'Exportacion'
	        ]
	    );

	    $this->assertNotEmpty(
	        (string)
	        $comprobante['Sello']
	    );

	    $this->assertNotEmpty(
	        (string)
	        $comprobante[
	            'Certificado'
	        ]
	    );

	    $this->assertNotEmpty(
	        (string)
	        $comprobante[
	            'NoCertificado'
	        ]
	    );
	}
	
	public function test_cfdi_uses_invoice_fiscal_snapshots(): void
	{
	    $invoice =
	        $this->createInvoice();

	    $result =
	        app(
	            BuildInvoiceCfdiXml::class
	        )->execute(
	            $invoice,
	            $this->certificate
	        );

	    $cfdi =
	        Cfdi::newFromString(
	            $result->xml
	        );

	    $comprobante =
	        $cfdi->getNode();

	    $emisor =
	        $comprobante->searchNode(
	            'cfdi:Emisor'
	        );

	    $receptor =
	        $comprobante->searchNode(
	            'cfdi:Receptor'
	        );

	    $this->assertNotNull(
	        $emisor
	    );

	    $this->assertNotNull(
	        $receptor
	    );

	    $this->assertSame(
	        $invoice->issuer_rfc,
	        (string) $emisor['Rfc']
	    );

	    $this->assertSame(
	        $invoice->issuer_name,
	        (string) $emisor['Nombre']
	    );

	    $this->assertSame(
	        $invoice->receiver_rfc,
	        (string) $receptor['Rfc']
	    );

	    $this->assertSame(
	        $invoice->receiver_name,
	        (string) $receptor['Nombre']
	    );

	    $this->assertSame(
	        $invoice
	            ->receiver_postal_code,
	        (string)
	        $receptor[
	            'DomicilioFiscalReceptor'
	        ]
	    );
	}
	
	public function test_cfdi_contains_concept_and_transferred_tax(): void
	{
	    $invoice =
	        $this->createInvoice();

	    $result =
	        app(
	            BuildInvoiceCfdiXml::class
	        )->execute(
	            $invoice,
	            $this->certificate
	        );

	    $cfdi =
	        Cfdi::newFromString(
	            $result->xml
	        );

	    $comprobante =
	        $cfdi->getNode();

	    $concepto =
	        $comprobante->searchNode(
	            'cfdi:Conceptos',
	            'cfdi:Concepto'
	        );

	    $this->assertNotNull(
	        $concepto
	    );

	    $this->assertSame(
	        '01010101',
	        (string)
	        $concepto[
	            'ClaveProdServ'
	        ]
	    );

	    $this->assertSame(
	        'H87',
	        (string)
	        $concepto[
	            'ClaveUnidad'
	        ]
	    );

	    $this->assertSame(
	        '02',
	        (string)
	        $concepto[
	            'ObjetoImp'
	        ]
	    );

	    $traslado =
	        $concepto->searchNode(
	            'cfdi:Impuestos',
	            'cfdi:Traslados',
	            'cfdi:Traslado'
	        );

	    $this->assertNotNull(
	        $traslado
	    );

	    $this->assertSame(
	        '002',
	        (string)
	        $traslado['Impuesto']
	    );

	    $this->assertSame(
	        'Tasa',
	        (string)
	        $traslado['TipoFactor']
	    );

	    $this->assertSame(
	        '0.160000',
	        (string)
	        $traslado['TasaOCuota']
	    );
	}

	public function test_invoice_cannot_use_certificate_from_other_company(): void
	{
	    $invoice =
	        $this->createInvoice();

	    $otherCompany =
	        Company::factory()->create();

	    $otherCertificate =
	        FiscalCertificate::factory()
	            ->for($otherCompany)
	            ->create([
	                'status' =>
	                    FiscalCertificateStatus::Active
	                        ->value,

	                'valid_from' =>
	                    now()->subDay(),

	                'valid_until' =>
	                    now()->addYear(),
	            ]);

	    $this->expectException(
	        \App\Domain\Fiscal\Exceptions\CfdiBuildException::class
	    );

	    app(
	        BuildInvoiceCfdiXml::class
	    )->execute(
	        $invoice,
	        $otherCertificate
	    );
	}
}
