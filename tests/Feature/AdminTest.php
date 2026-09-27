<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\InteractsWithTenancy;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use InteractsWithTenancy, RefreshDatabase;

    public function test_a_super_admin_can_list_every_tenant(): void
    {
        Tenant::factory()->count(3)->create();

        $this->actingAs(User::factory()->superAdmin()->create());

        $this->getJson('/api/v1/admin/tenants')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_regular_members_are_forbidden_from_the_admin_area(): void
    {
        $tenant = $this->createTenant('Plain');
        $this->actingAsMember($tenant);

        $this->get('/admin')->assertForbidden();
        $this->getJson('/api/v1/admin/tenants')->assertForbidden();
    }

    public function test_a_super_admin_creates_a_tenant_and_grants_its_owner_access(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create());

        $this->postJson('/api/v1/admin/tenants', [
            'name' => 'Client Co',
            'owner_name' => 'Client Owner',
            'owner_email' => 'client@example.com',
            'owner_password' => 'secret-password',
        ])->assertCreated()->assertJsonPath('owner.email', 'client@example.com');

        $this->assertDatabaseHas('tenants', ['name' => 'Client Co', 'locale' => 'es']);
        $this->assertDatabaseHas('users', ['email' => 'client@example.com']);

        $tenant = Tenant::where('name', 'Client Co')->firstOrFail();
        $owner = User::where('email', 'client@example.com')->firstOrFail();

        $this->assertTrue($owner->belongsToTenant($tenant->id));
        $this->assertSame('es', $tenant->locale);

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $this->assertTrue($owner->hasRole('owner'));
    }

    public function test_a_super_admin_grants_access_to_an_existing_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $this->actingAs(User::factory()->superAdmin()->create());

        $this->postJson('/api/v1/admin/tenants/'.$tenant->uuid.'/members', [
            'name' => 'New Member',
            'email' => 'member@example.com',
            'role' => 'member',
        ])->assertCreated();

        $user = User::where('email', 'member@example.com')->firstOrFail();
        $this->assertTrue($user->belongsToTenant($tenant->id));

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $this->assertTrue($user->hasRole('member'));
    }
}
