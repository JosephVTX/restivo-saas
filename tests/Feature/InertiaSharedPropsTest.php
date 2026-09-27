<?php

namespace Tests\Feature;

use App\Enums\Role as RoleEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\InteractsWithTenancy;
use Tests\TestCase;

class InertiaSharedPropsTest extends TestCase
{
    use InteractsWithTenancy, RefreshDatabase;

    public function test_inertia_pages_share_the_active_tenant_and_role_permissions(): void
    {
        $tenant = $this->createTenant('Restaurante Demo');
        $this->actingAsMember($tenant, RoleEnum::Owner->value);

        $this->get(route('app.dashboard'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('app/Dashboard')
                ->where('tenant.uuid', $tenant->uuid)
                ->where('auth.roles', [RoleEnum::Owner->value])
                ->where('auth.permissions', fn ($permissions): bool => $permissions->contains('reports.view')));
    }
}
