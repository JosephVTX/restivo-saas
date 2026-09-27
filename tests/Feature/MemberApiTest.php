<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\InteractsWithTenancy;
use Tests\TestCase;

class MemberApiTest extends TestCase
{
    use InteractsWithTenancy, RefreshDatabase;

    public function test_an_owner_adds_a_member_to_the_active_tenant(): void
    {
        $tenant = $this->createTenant('Team');
        $this->actingAsMember($tenant, 'owner');

        $this->postJson('/api/v1/members', [
            'email' => 'newbie@example.com',
            'role' => 'member',
            'job_title' => 'Developer',
        ])->assertCreated()->assertJsonPath('data.user.email', 'newbie@example.com');

        $user = User::where('email', 'newbie@example.com')->firstOrFail();

        $this->assertTrue($user->belongsToTenant($tenant->id));
        $this->assertDatabaseHas('memberships', [
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'job_title' => 'Developer',
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $this->assertTrue($user->hasRole('member'));
    }

    public function test_adding_the_same_member_twice_is_rejected(): void
    {
        $tenant = $this->createTenant('Team');
        $this->actingAsMember($tenant, 'owner');

        $payload = ['email' => 'newbie@example.com', 'role' => 'member'];

        $this->postJson('/api/v1/members', $payload)->assertCreated();
        $this->postJson('/api/v1/members', $payload)->assertStatus(422);
    }

    public function test_members_index_is_tenant_scoped_and_paginated(): void
    {
        $alpha = $this->createTenant('Alpha');
        $beta = $this->createTenant('Beta');

        $this->actingAsMember($alpha, 'owner');
        $this->createMember($beta, 'owner');

        $this->getJson('/api/v1/members')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure(['data', 'meta', 'links']);
    }
}
