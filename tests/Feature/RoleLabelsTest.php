<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithTenancy;
use Tests\TestCase;

class RoleLabelsTest extends TestCase
{
    use InteractsWithTenancy, RefreshDatabase;

    public function test_roles_and_permissions_are_exposed_with_spanish_labels(): void
    {
        $tenant = $this->createTenant('Labels');
        $this->actingAsMember($tenant);

        $roles = collect($this->getJson('/api/v1/roles')->assertOk()->json('data'));

        $this->assertSame('Propietario', $roles->firstWhere('name', 'owner')['label']);
        $this->assertSame('Administrador', $roles->firstWhere('name', 'admin')['label']);
        $this->assertSame('Miembro', $roles->firstWhere('name', 'member')['label']);

        $permissionLabels = collect($roles->firstWhere('name', 'owner')['permissions'])->pluck('label')->all();

        $this->assertContains('Ver proyectos', $permissionLabels);
        $this->assertNotContains('projects.view', $permissionLabels);
    }
}
