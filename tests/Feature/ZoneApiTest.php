<?php

namespace Tests\Feature;

use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithTenancy;
use Tests\TestCase;

class ZoneApiTest extends TestCase
{
    use InteractsWithTenancy, RefreshDatabase;

    public function test_lists_only_the_zones_of_the_active_tenant(): void
    {
        $alpha = $this->createTenant('Alpha');
        $beta = $this->createTenant('Beta');

        Zone::factory()->count(2)->create(['tenant_id' => $alpha->id]);
        Zone::factory()->count(3)->create(['tenant_id' => $beta->id]);

        $this->actingAsMember($alpha);

        $this->getJson('/api/v1/zones')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_creates_a_zone_scoped_to_the_active_tenant(): void
    {
        $tenant = $this->createTenant('Creator');
        $this->actingAsMember($tenant);

        $this->postJson('/api/v1/zones', ['name' => 'Terraza', 'sort_order' => 1, 'is_active' => true])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Terraza');

        $this->assertDatabaseHas('zones', [
            'tenant_id' => $tenant->id,
            'name' => 'Terraza',
        ]);
    }

    public function test_cannot_access_a_zone_from_another_tenant(): void
    {
        $alpha = $this->createTenant('Alpha');
        $beta = $this->createTenant('Beta');

        $foreign = Zone::factory()->create(['tenant_id' => $beta->id]);

        $this->actingAsMember($alpha);

        $this->getJson('/api/v1/zones/'.$foreign->uuid)->assertNotFound();
    }

    public function test_validates_the_zone_payload(): void
    {
        $tenant = $this->createTenant('Validator');
        $this->actingAsMember($tenant);

        $this->postJson('/api/v1/zones', ['name' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_updates_and_deletes_a_zone(): void
    {
        $tenant = $this->createTenant('Editor');
        $this->actingAsMember($tenant);

        $zone = Zone::factory()->create(['tenant_id' => $tenant->id]);

        $this->patchJson('/api/v1/zones/'.$zone->uuid, ['name' => 'Renombrada'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Renombrada');

        $this->deleteJson('/api/v1/zones/'.$zone->uuid)->assertNoContent();

        $this->assertSoftDeleted('zones', ['id' => $zone->id]);
    }
}
