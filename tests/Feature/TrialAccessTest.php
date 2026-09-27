<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithTenancy;
use Tests\TestCase;

class TrialAccessTest extends TestCase
{
    use InteractsWithTenancy, RefreshDatabase;

    public function test_owner_can_log_in_while_the_trial_is_running(): void
    {
        $tenant = Tenant::factory()->trial()->create();
        $user = User::factory()->forTenant($tenant, 'owner')->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_expired_trial_blocks_the_owner_from_logging_in(): void
    {
        $tenant = Tenant::factory()->expiredTrial()->create();
        $user = User::factory()->forTenant($tenant, 'owner')->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_expired_trial_blocks_members_from_logging_in(): void
    {
        $tenant = Tenant::factory()->expiredTrial()->create();
        $user = User::factory()->forTenant($tenant, 'waiter')->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_member_with_a_second_active_tenant_can_still_log_in(): void
    {
        $expired = Tenant::factory()->expiredTrial()->create();
        $active = Tenant::factory()->create();

        $user = User::factory()->forTenant($expired, 'owner')->create();
        $user->memberships()->create(['tenant_id' => $active->getKey(), 'joined_at' => now()]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_super_admin_can_log_in_without_a_tenant(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_expired_trial_blocks_tenant_pages_for_logged_in_members(): void
    {
        $tenant = Tenant::factory()->expiredTrial()->create();
        $this->actingAsMember($tenant, 'owner');

        $this->get('/app')->assertForbidden();
    }

    public function test_running_trial_allows_tenant_pages(): void
    {
        $tenant = Tenant::factory()->trial()->create();
        $this->actingAsMember($tenant, 'owner');

        $this->get('/app')->assertOk();
    }

    public function test_tenant_is_active_only_until_the_trial_expires(): void
    {
        $running = Tenant::factory()->trial()->create();
        $expired = Tenant::factory()->expiredTrial()->create();
        $active = Tenant::factory()->create();

        $this->assertTrue($running->isActive());
        $this->assertTrue($active->isActive());
        $this->assertFalse($expired->isActive());
        $this->assertTrue($expired->hasExpired());
    }

    public function test_tenant_reports_when_it_is_about_to_expire(): void
    {
        $soon = Tenant::factory()->expiringSoon()->create();
        $later = Tenant::factory()->trial()->create();

        $this->assertTrue($soon->isExpiringSoon());
        $this->assertSame(3, $soon->daysUntilExpiry());
        $this->assertFalse($later->isExpiringSoon());
        $this->assertNull(Tenant::factory()->create()->daysUntilExpiry());
    }
}
