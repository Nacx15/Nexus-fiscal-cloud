<?php

namespace Tests\Feature;

use App\Domain\Fiscal\Enums\FiscalCertificateStatus;
use App\Domain\Fiscal\Enums\FiscalDocumentType;
use App\Models\Company;
use App\Models\CompanyFiscalProfile;
use App\Models\FiscalCertificate;
use App\Models\FiscalSequence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FiscalReadinessTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Company $company;

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

        Sanctum::actingAs(
            $this->user
        );
    }

    public function test_company_without_fiscal_configuration_is_not_ready(): void
    {
        $this
            ->withHeader(
                'X-Company-ID',
                (string) $this->company->id
            )
            ->getJson(
                '/api/v1/company/fiscal-readiness'
            )
            ->assertOk()
            ->assertJsonPath(
                'data.ready',
                false
            )
            ->assertJsonPath(
                'data.checks.fiscal_profile.ready',
                false
            )
            ->assertJsonPath(
                'data.checks.fiscal_certificate.ready',
                false
            )
            ->assertJsonPath(
                'data.checks.invoice_sequence.ready',
                false
            );
    }

    public function test_company_with_complete_configuration_is_ready(): void
    {
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

        FiscalSequence::factory()
            ->for($this->company)
            ->create([
                'document_type' =>
                    FiscalDocumentType::Invoice->value,

                'series' =>
                    'A',

                'status' =>
                    'active',
            ]);

        $this
            ->withHeader(
                'X-Company-ID',
                (string) $this->company->id
            )
            ->getJson(
                '/api/v1/company/fiscal-readiness'
            )
            ->assertOk()
            ->assertJsonPath(
                'data.ready',
                true
            )
            ->assertJsonPath(
                'data.checks.fiscal_profile.ready',
                true
            )
            ->assertJsonPath(
                'data.checks.fiscal_certificate.ready',
                true
            )
            ->assertJsonPath(
                'data.checks.invoice_sequence.ready',
                true
            );
    }

    public function test_expired_active_certificate_does_not_make_company_ready(): void
    {
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
                    now()->subYears(2),

                'valid_until' =>
                    now()->subYear(),

                'status' =>
                    FiscalCertificateStatus::Active->value,
            ]);

        FiscalSequence::factory()
            ->for($this->company)
            ->create([
                'document_type' =>
                    FiscalDocumentType::Invoice->value,

                'status' =>
                    'active',
            ]);

        $this
            ->withHeader(
                'X-Company-ID',
                (string) $this->company->id
            )
            ->getJson(
                '/api/v1/company/fiscal-readiness'
            )
            ->assertOk()
            ->assertJsonPath(
                'data.ready',
                false
            )
            ->assertJsonPath(
                'data.checks.fiscal_certificate.ready',
                false
            );
    }
}
