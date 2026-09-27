<?php

namespace Tests\Feature;

use App\Enums\TenantStatus;
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

    public function test_a_super_admin_starts_a_trial_period(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create());

        $this->postJson('/api/v1/admin/tenants', [
            'name' => 'Trial Co',
            'duration' => '14_days',
            'owner_name' => 'Owner',
            'owner_email' => 'trial@example.com',
            'owner_password' => 'secret-password',
        ])->assertCreated();

        $tenant = Tenant::where('name', 'Trial Co')->firstOrFail();

        $this->assertSame(TenantStatus::Trial, $tenant->status);
        $this->assertSame(14, $tenant->daysUntilExpiry());
        $this->assertTrue($tenant->isActive());
    }

    public function test_a_super_admin_starts_a_paid_period(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create());

        $this->postJson('/api/v1/admin/tenants', [
            'name' => 'Paid Co',
            'duration' => '3_months',
            'owner_name' => 'Owner',
            'owner_email' => 'paid@example.com',
            'owner_password' => 'secret-password',
        ])->assertCreated();

        $tenant = Tenant::where('name', 'Paid Co')->firstOrFail();

        $this->assertSame(TenantStatus::Active, $tenant->status);
        $this->assertNotNull($tenant->expires_at);
        $this->assertTrue($tenant->expires_at->between(now()->addMonths(3)->subDay(), now()->addMonths(3)));
    }

    public function test_a_super_admin_renews_an_expired_tenant(): void
    {
        $tenant = Tenant::factory()->expiredTrial()->create();
        $this->actingAs(User::factory()->superAdmin()->create());

        $this->patchJson('/api/v1/admin/tenants/'.$tenant->uuid, [
            'duration' => '12_months',
        ])->assertOk();

        $tenant->refresh();

        $this->assertSame(TenantStatus::Active, $tenant->status);
        $this->assertTrue($tenant->isActive());
        $this->assertFalse($tenant->hasExpired());
    }

    public function test_a_super_admin_enters_and_leaves_a_tenant_workspace(): void
    {
        $tenant = Tenant::factory()->create();
        $this->actingAs(User::factory()->superAdmin()->create());

        $this->post('/admin/tenants/'.$tenant->uuid.'/enter')
            ->assertRedirect(route('app.dashboard'));

        $this->assertSame($tenant->getKey(), session(config('tenancy.session_key', 'tenant_id')));

        $this->get('/app')->assertOk();

        $this->post('/admin/leave')->assertRedirect(route('admin.dashboard'));

        $this->assertNull(session(config('tenancy.session_key', 'tenant_id')));
    }

    public function test_a_super_admin_cannot_enter_an_inactive_tenant(): void
    {
        $tenant = Tenant::factory()->create(['status' => TenantStatus::Suspended]);
        $this->actingAs(User::factory()->superAdmin()->create());

        $this->from('/admin/tenants')
            ->post('/admin/tenants/'.$tenant->uuid.'/enter')
            ->assertRedirect('/admin/tenants')
            ->assertSessionHas('error');
    }

    public function test_regular_members_cannot_use_the_admin_workspace_switch(): void
    {
        $tenant = $this->createTenant('Plain');
        $this->actingAsMember($tenant, 'owner');

        $this->post('/admin/tenants/'.$tenant->uuid.'/enter')->assertForbidden();
        $this->post('/admin/leave')->assertForbidden();
    }
}
