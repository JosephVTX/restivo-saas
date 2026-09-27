<?php

namespace Tests\Feature;

use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithTenancy;
use Tests\TestCase;

class ProjectApiTest extends TestCase
{
    use InteractsWithTenancy, RefreshDatabase;

    public function test_lists_only_the_projects_of_the_active_tenant(): void
    {
        $alpha = $this->createTenant('Alpha');
        $beta = $this->createTenant('Beta');

        Project::factory()->count(2)->create(['tenant_id' => $alpha->id]);
        Project::factory()->count(4)->create(['tenant_id' => $beta->id]);

        $this->actingAsMember($alpha);

        $this->getJson('/api/v1/projects')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_creates_a_project_scoped_to_the_active_tenant(): void
    {
        $tenant = $this->createTenant('Creator');
        $this->actingAsMember($tenant);

        $this->postJson('/api/v1/projects', [
            'name' => 'New initiative',
            'status' => 'active',
        ])->assertCreated()->assertJsonPath('data.name', 'New initiative');

        $this->assertDatabaseHas('projects', [
            'tenant_id' => $tenant->id,
            'name' => 'New initiative',
        ]);
    }

    public function test_cannot_access_a_project_from_another_tenant(): void
    {
        $alpha = $this->createTenant('Alpha');
        $beta = $this->createTenant('Beta');

        $foreign = Project::factory()->create(['tenant_id' => $beta->id]);

        $this->actingAsMember($alpha);

        $this->getJson('/api/v1/projects/'.$foreign->uuid)->assertNotFound();
    }

    public function test_validates_the_project_payload(): void
    {
        $tenant = $this->createTenant('Validator');
        $this->actingAsMember($tenant);

        $this->postJson('/api/v1/projects', ['name' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_updates_and_deletes_a_project(): void
    {
        $tenant = $this->createTenant('Editor');
        $this->actingAsMember($tenant);

        $project = Project::factory()->create(['tenant_id' => $tenant->id]);

        $this->patchJson('/api/v1/projects/'.$project->uuid, ['name' => 'Renamed'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Renamed');

        $this->deleteJson('/api/v1/projects/'.$project->uuid)->assertNoContent();

        $this->assertSoftDeleted('projects', ['id' => $project->id]);
    }
}
