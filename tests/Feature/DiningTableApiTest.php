<?php

namespace Tests\Feature;

use App\Models\DiningTable;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithTenancy;
use Tests\TestCase;

class DiningTableApiTest extends TestCase
{
    use InteractsWithTenancy, RefreshDatabase;

    public function test_lists_only_the_tables_of_the_active_tenant(): void
    {
        $alpha = $this->createTenant('Alpha');
        $beta = $this->createTenant('Beta');

        DiningTable::factory()->count(2)->create(['tenant_id' => $alpha->id]);
        DiningTable::factory()->count(4)->create(['tenant_id' => $beta->id]);

        $this->actingAsMember($alpha);

        $this->getJson('/api/v1/dining-tables')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_creates_a_table_scoped_to_the_active_tenant(): void
    {
        $tenant = $this->createTenant('Creator');
        $zone = Zone::factory()->create(['tenant_id' => $tenant->id]);
        $this->actingAsMember($tenant);

        $this->postJson('/api/v1/dining-tables', [
            'zone_id' => $zone->id,
            'name' => 'Mesa 5',
            'capacity' => 4,
            'status' => 'available',
        ])->assertCreated()->assertJsonPath('data.name', 'Mesa 5');

        $this->assertDatabaseHas('dining_tables', [
            'tenant_id' => $tenant->id,
            'name' => 'Mesa 5',
        ]);
    }

    public function test_cannot_access_a_table_from_another_tenant(): void
    {
        $alpha = $this->createTenant('Alpha');
        $beta = $this->createTenant('Beta');

        $foreign = DiningTable::factory()->create(['tenant_id' => $beta->id]);

        $this->actingAsMember($alpha);

        $this->getJson('/api/v1/dining-tables/'.$foreign->uuid)->assertNotFound();
    }

    public function test_validates_the_table_payload(): void
    {
        $tenant = $this->createTenant('Validator');
        $this->actingAsMember($tenant);

        $this->postJson('/api/v1/dining-tables', ['name' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_filters_tables_by_zone_uuid(): void
    {
        $tenant = $this->createTenant('Filtered');
        $this->actingAsMember($tenant);

        $zone = Zone::factory()->create(['tenant_id' => $tenant->id]);
        $other = Zone::factory()->create(['tenant_id' => $tenant->id]);

        DiningTable::factory()->count(2)->create(['tenant_id' => $tenant->id, 'zone_id' => $zone->id]);
        DiningTable::factory()->create(['tenant_id' => $tenant->id, 'zone_id' => $other->id]);

        $this->getJson('/api/v1/dining-tables?filter[zone]='.$zone->uuid)
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_updates_and_deletes_a_table(): void
    {
        $tenant = $this->createTenant('Editor');
        $this->actingAsMember($tenant);

        $table = DiningTable::factory()->create(['tenant_id' => $tenant->id]);

        $this->patchJson('/api/v1/dining-tables/'.$table->uuid, ['name' => 'Mesa 9', 'capacity' => 8])
            ->assertOk()
            ->assertJsonPath('data.name', 'Mesa 9');

        $this->deleteJson('/api/v1/dining-tables/'.$table->uuid)->assertNoContent();

        $this->assertSoftDeleted('dining_tables', ['id' => $table->id]);
    }
}
