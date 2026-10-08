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

class FiscalCertificateTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Company $company;

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

        Sanctum::actingAs(
            $this->user
        );
    }

    public function test_company_can_upload_certificate_files(): void
    {
         $this->withoutExceptionHandling();

	    $response = $this
	        ->withHeader(
	            'X-Company-ID',
	            (string) $this->company->id
	        )
	        ->post(
	            '/api/v1/company/fiscal-certificates',
                [
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
                        'super-secret-password',
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
        $this
            ->withHeader(
                'X-Company-ID',
                (string) $this->company->id
            )
            ->post(
                '/api/v1/company/fiscal-certificates',
                [
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
                        'super-secret-password',
                ]
            )
            ->assertCreated();

        $rawValue = DB::table(
            'fiscal_certificates'
        )->value(
            'private_key_password'
        );

        $this->assertNotSame(
            'super-secret-password',
            $rawValue
        );

        $this->assertNotEmpty(
            $rawValue
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
}
