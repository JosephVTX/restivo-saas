<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Services\Tenancy\TenantProvisioner;
use App\Support\Tenancy\TenantContext;
use App\Support\Tenancy\TenantResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\InteractsWithTenancy;
use Tests\TestCase;

class TenancyIsolationTest extends TestCase
{
    use InteractsWithTenancy, RefreshDatabase;

    public function test_resolves_tenants_by_id_uuid_and_slug(): void
    {
        $tenant = $this->createTenant('Resolver Co');
        $resolver = app(TenantResolver::class);

        $this->assertSame($tenant->uuid, $resolver->findById($tenant->id)?->uuid);
        $this->assertSame($tenant->id, $resolver->findByUuid($tenant->uuid)?->id);
        $this->assertSame($tenant->id, $resolver->findBySlug($tenant->slug)?->id);
        $this->assertSame($tenant->uuid, $resolver->find((string) $tenant->id)?->uuid);
    }

    public function test_scopes_queries_to_the_resolved_tenant(): void
    {
        $alpha = $this->createTenant('Alpha');
        $beta = $this->createTenant('Beta');

        Project::factory()->count(3)->create(['tenant_id' => $alpha->id]);
        Project::factory()->count(5)->create(['tenant_id' => $beta->id]);

        app(TenantContext::class)->set($alpha);

        $this->assertSame(3, Project::query()->count());
        $this->assertSame(8, Project::withoutTenantScope()->count());
    }

    public function test_auto_fills_tenant_id_from_context_on_create(): void
    {
        $tenant = $this->createTenant('AutoFill');

        app(TenantContext::class)->set($tenant);

        $project = Project::create(['name' => 'Scoped project', 'status' => 'draft']);

        $this->assertSame($tenant->id, $project->tenant_id);
    }

    public function test_provisions_default_roles_when_a_tenant_is_created(): void
    {
        $tenant = $this->createTenant('Provisioned');

        TenantProvisioner::syncPermissions();
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

        $this->assertSame(
            ['owner', 'admin', 'cashier', 'waiter', 'kitchen', 'member'],
            Role::where('tenant_id', $tenant->id)->orderBy('id')->pluck('name')->all(),
        );
    }
}
