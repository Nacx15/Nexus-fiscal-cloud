<?php

namespace Tests\Feature;

use App\Domain\Fiscal\Enums\FiscalCertificateStatus;
use App\Models\Company;
use App\Models\FiscalCertificate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FiscalCertificateActivationTest extends TestCase
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

    public function test_valid_certificate_can_be_activated(): void
    {
        $certificate =
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
                        FiscalCertificateStatus::Inactive->value,
                ]);

        $this
            ->withHeader(
                'X-Company-ID',
                (string) $this->company->id
            )
            ->postJson(
                "/api/v1/company/fiscal-certificates/{$certificate->id}/activate"
            )
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                'active'
            );

        $this->assertDatabaseHas(
            'fiscal_certificates',
            [
                'id' =>
                    $certificate->id,

                'status' =>
                    'active',
            ]
        );
    }

    public function test_expired_certificate_cannot_be_activated(): void
    {
        $certificate =
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
                        FiscalCertificateStatus::Inactive->value,
                ]);

        $this
            ->withHeader(
                'X-Company-ID',
                (string) $this->company->id
            )
            ->postJson(
                "/api/v1/company/fiscal-certificates/{$certificate->id}/activate"
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'certificate',
            ]);
    }

    public function test_activating_new_certificate_deactivates_previous_one(): void
    {
        $old =
            FiscalCertificate::factory()
                ->for($this->company)
                ->create([
                    'certificate_number' =>
                        '30001000000500000001',

                    'fingerprint_sha256' =>
                        str_repeat('a', 64),

                    'valid_from' =>
                        now()->subMonth(),

                    'valid_until' =>
                        now()->addYear(),

                    'status' =>
                        FiscalCertificateStatus::Active->value,
                ]);

        $new =
            FiscalCertificate::factory()
                ->for($this->company)
                ->create([
                    'certificate_number' =>
                        '30001000000500000002',

                    'fingerprint_sha256' =>
                        str_repeat('b', 64),

                    'valid_from' =>
                        now()->subDay(),

                    'valid_until' =>
                        now()->addYear(),

                    'status' =>
                        FiscalCertificateStatus::Inactive->value,
                ]);

        $this
            ->withHeader(
                'X-Company-ID',
                (string) $this->company->id
            )
            ->postJson(
                "/api/v1/company/fiscal-certificates/{$new->id}/activate"
            )
            ->assertOk();

        $this->assertDatabaseHas(
            'fiscal_certificates',
            [
                'id' =>
                    $old->id,

                'status' =>
                    'inactive',
            ]
        );

        $this->assertDatabaseHas(
            'fiscal_certificates',
            [
                'id' =>
                    $new->id,

                'status' =>
                    'active',
            ]
        );
    }

    public function test_certificate_from_other_company_cannot_be_activated(): void
    {
        $otherCompany =
            Company::factory()->create();

        $certificate =
            FiscalCertificate::factory()
                ->for($otherCompany)
                ->create([
                    'certificate_number' =>
                        '30001000000500003416',

                    'fingerprint_sha256' =>
                        str_repeat('a', 64),

                    'valid_from' =>
                        now()->subDay(),

                    'valid_until' =>
                        now()->addYear(),
                ]);

        $this
            ->withHeader(
                'X-Company-ID',
                (string) $this->company->id
            )
            ->postJson(
                "/api/v1/company/fiscal-certificates/{$certificate->id}/activate"
            )
            ->assertNotFound();
    }
}
