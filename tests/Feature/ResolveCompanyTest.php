<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResolveCompanyTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_header_is_required(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->getJson(
            '/api/v1/context'
        );

        $response
            ->assertBadRequest()
            ->assertJson([
                'code' => 'company_context_required',
            ]);
    }

    public function test_user_cannot_access_company_without_membership(): void
    {
        $user = User::factory()->create();

        $company = Company::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->withHeader(
            'X-Company-ID',
            (string) $company->id
        )->getJson('/api/v1/context');

        $response
            ->assertForbidden()
            ->assertJson([
                'code' => 'company_access_denied',
            ]);
    }

    public function test_inactive_membership_cannot_access_company(): void
    {
        $user = User::factory()->create();

        $company = Company::factory()->create();

        $user->companies()->attach(
            $company->id,
            [
                'role' => 'viewer',
                'status' => 'inactive',
                'joined_at' => now(),
            ]
        );

        Sanctum::actingAs($user);

        $response = $this->withHeader(
            'X-Company-ID',
            (string) $company->id
        )->getJson('/api/v1/context');

        $response->assertForbidden();
    }

    public function test_inactive_company_cannot_be_selected(): void
    {
        $user = User::factory()->create();

        $company = Company::factory()->create([
            'status' => 'inactive',
        ]);

        $user->companies()->attach(
            $company->id,
            [
                'role' => 'owner',
                'status' => 'active',
                'joined_at' => now(),
            ]
        );

        Sanctum::actingAs($user);

        $response = $this->withHeader(
            'X-Company-ID',
            (string) $company->id
        )->getJson('/api/v1/context');

        $response->assertForbidden();
    }

    public function test_active_member_can_resolve_company_context(): void
    {
        $user = User::factory()->create();

        $company = Company::factory()->create([
            'name' => 'Empresa Demo',
            'slug' => 'empresa-demo',
            'status' => 'active',
        ]);

        $user->companies()->attach(
            $company->id,
            [
                'role' => 'owner',
                'status' => 'active',
                'joined_at' => now(),
            ]
        );

        Sanctum::actingAs($user);

        $response = $this->withHeader(
            'X-Company-ID',
            (string) $company->id
        )->getJson('/api/v1/context');

        $response
            ->assertOk()
            ->assertJson([
                'company' => [
                    'id' => $company->id,
                    'name' => 'Empresa Demo',
                    'slug' => 'empresa-demo',
                ],
            ]);
    }
}
