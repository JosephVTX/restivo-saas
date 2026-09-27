<?php

namespace Tests\Concerns;

use App\Models\Tenant;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

trait InteractsWithTenancy
{
    protected function createTenant(string $name = 'Acme Inc'): Tenant
    {
        return Tenant::factory()->create(['name' => $name]);
    }

    protected function createMember(Tenant $tenant, string $role = 'owner'): User
    {
        $user = User::factory()->create();

        $tenant->memberships()->create([
            'user_id' => $user->getKey(),
            'joined_at' => now(),
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->getKey());
        $user->assignRole($role);

        return $user;
    }

    protected function actingAsMember(Tenant $tenant, string $role = 'owner'): User
    {
        $user = $this->createMember($tenant, $role);

        $this->actingAs($user);
        $this->withSession([config('tenancy.session_key', 'tenant_id') => $tenant->getKey()]);

        return $user;
    }
}
