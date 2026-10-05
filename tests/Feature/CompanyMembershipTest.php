<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyMembershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_belong_to_multiple_companies(): void
    {
        $user = User::factory()->create();

        $companyA = Company::factory()->create();

        $companyB = Company::factory()->create();

        $user->companies()->attach(
            $companyA->id,
            [
                'role' => 'owner',
                'status' => 'active',
                'joined_at' => now(),
            ]
        );

        $user->companies()->attach(
            $companyB->id,
            [
                'role' => 'billing',
                'status' => 'active',
                'joined_at' => now(),
            ]
        );

        $user = $user->fresh();

        $this->assertCount(
            2,
            $user->companies
        );

        $this->assertTrue(
            $user->companies->contains($companyA)
        );

        $this->assertTrue(
            $user->companies->contains($companyB)
        );
    }
}
