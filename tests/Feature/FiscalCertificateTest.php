<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use App\Models\CompanyFiscalProfile;
use App\Models\FiscalCertificate;

class FiscalCertificateTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Company $company;

	private function csdPath(
	    string $filename
	): string {
	    return base_path(
	        'tests/Fixtures/csd/' . $filename
	    );
	}

	private function csdPassword(): string
	{
	     $password = file_get_contents(
	        $this->csdPath(
	            'EKU9003173C9.password.txt'
	        )
	    );

	    $this->assertNotFalse($password);

	    return trim($password);
	}

    	protected function setUp(): void
	{
	    parent::setUp();

	    Storage::fake(
	        'fiscal_certificates'
	    );

	    /*
	    |--------------------------------------------------------------------------
	    | User
	    |--------------------------------------------------------------------------
	    */

	    $this->user =
	        User::factory()->create();

	    /*
	    |--------------------------------------------------------------------------
	    | Company
	    |--------------------------------------------------------------------------
	    */

	    $this->company =
	        Company::factory()->create();

	    /*
	    |--------------------------------------------------------------------------
	    | Fiscal Profile
	    |--------------------------------------------------------------------------
	    |
	    | Tiene que crearse DESPUÉS de Company porque pertenece a ella.
	    |
	    */

	    CompanyFiscalProfile::factory()
	        ->for($this->company)
	        ->create([
	            'rfc' => 'EKU9003173C9',
	        ]);

	    /*
	    |--------------------------------------------------------------------------
	    | Membership
	    |--------------------------------------------------------------------------
	    */

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

	    /*
	    |--------------------------------------------------------------------------
	    | Authentication
	    |--------------------------------------------------------------------------
	    */

	    Sanctum::actingAs(
	        $this->user
	    );
	}

    	public function test_company_can_upload_certificate_files(): void
	{
	    $response = $this
	        ->withHeader(
	            'Accept',
	            'application/json'
	        )
	        ->withHeader(
	            'X-Company-ID',
	            (string) $this->company->id
	        )
	        ->post(
	            '/api/v1/company/fiscal-certificates',
	            [
	                'certificate_file' =>
	                    new UploadedFile(
	                        $this->csdPath(
	                            'EKU9003173C9.cer'
	                        ),
	                        'EKU9003173C9.cer',
	                        null,
	                        null,
	                        true
	                    ),

	                'private_key_file' =>
	                    new UploadedFile(
	                        $this->csdPath(
	                            'EKU9003173C9.key'
	                        ),
	                        'EKU9003173C9.key',
	                        null,
	                        null,
	                        true
	                    ),

	                'private_key_password' =>
	                    $this->csdPassword(),
	            ]
	        );

	    $response
	        ->assertCreated()
	        ->assertJsonMissing([
	            'private_key_password',
	            'certificate_path',
	            'private_key_path',
	        ]);

	    $this->assertDatabaseHas(
	        'fiscal_certificates',
	        [
	            'company_id' =>
	                $this->company->id,

	            'status' =>
	                'inactive',
	        ]
	    );
	}

	public function test_private_key_password_is_not_stored_as_plain_text(): void
	{
	    $password =
	        $this->csdPassword();

	    $this
	        ->withHeader(
	            'Accept',
	            'application/json'
	        )
	        ->withHeader(
	            'X-Company-ID',
	            (string) $this->company->id
	        )
	        ->post(
	            '/api/v1/company/fiscal-certificates',
	            [
	                'certificate_file' =>
	                    new UploadedFile(
	                        $this->csdPath(
	                            'EKU9003173C9.cer'
	                        ),
	                        'EKU9003173C9.cer',
	                        null,
	                        null,
	                        true
	                    ),

	                'private_key_file' =>
	                    new UploadedFile(
	                        $this->csdPath(
	                            'EKU9003173C9.key'
	                        ),
	                        'EKU9003173C9.key',
	                        null,
	                        null,
	                        true
	                    ),

	                'private_key_password' =>
	                    $password,
	            ]
	        )
	        ->assertCreated();

	    $rawValue = DB::table(
	        'fiscal_certificates'
	    )->value(
	        'private_key_password'
	    );

	    $this->assertNotEmpty(
	        $rawValue
	    );

	    $this->assertNotSame(
	        $password,
	        $rawValue
	    );

		$certificate =
		    FiscalCertificate::query()
		        ->firstOrFail();

		$this->assertSame(
		    $password,
		    $certificate->private_key_password
		);
	}

    public function test_company_id_cannot_be_supplied(): void
    {
        $otherCompany =
            Company::factory()->create();

        $this
            ->withHeader(
                'X-Company-ID',
                (string) $this->company->id
            )
            ->post(
                '/api/v1/company/fiscal-certificates',
                [
                    'company_id' =>
                        $otherCompany->id,

                    'certificate_file' =>
                        UploadedFile::fake()
                            ->create(
                                'certificate.cer',
                                10
                            ),

                    'private_key_file' =>
                        UploadedFile::fake()
                            ->create(
                                'private.key',
                                10
                            ),

                    'private_key_password' =>
                        'password',
                ]
            )
            ->assertSessionHasErrors(
                'company_id'
            );
    }

	public function test_wrong_private_key_password_is_rejected(): void
	{
	    $response = $this
	        ->withHeader(
	            'Accept',
	            'application/json'
	        )
	        ->withHeader(
	            'X-Company-ID',
	            (string) $this->company->id
	        )
	        ->post(
	            '/api/v1/company/fiscal-certificates',
	            [
	                'certificate_file' =>
	                    new UploadedFile(
	                        $this->csdPath(
	                            'EKU9003173C9.cer'
	                        ),
	                        'certificate.cer',
	                        null,
	                        null,
	                        true
	                    ),

	                'private_key_file' =>
	                    new UploadedFile(
	                        $this->csdPath(
	                            'EKU9003173C9.key'
	                        ),
	                        'private.key',
	                        null,
	                        null,
	                        true
	                    ),

	                'private_key_password' =>
	                    'wrong-password',
	            ]
	        );

	    $response
	        ->assertUnprocessable()
	        ->assertJsonValidationErrors([
	            'credential',
	        ]);

	    $this->assertDatabaseCount(
	        'fiscal_certificates',
	        0
	    );
	}
}
