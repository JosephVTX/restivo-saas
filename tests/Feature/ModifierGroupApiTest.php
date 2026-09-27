<?php

namespace Tests\Feature;

use App\Models\Modifier;
use App\Models\ModifierGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithTenancy;
use Tests\TestCase;

class ModifierGroupApiTest extends TestCase
{
    use InteractsWithTenancy, RefreshDatabase;

    public function test_lists_only_the_groups_of_the_active_tenant(): void
    {
        $alpha = $this->createTenant('Alpha');
        $beta = $this->createTenant('Beta');

        ModifierGroup::factory()->count(2)->create(['tenant_id' => $alpha->id]);
        ModifierGroup::factory()->count(3)->create(['tenant_id' => $beta->id]);

        $this->actingAsMember($alpha);

        $this->getJson('/api/v1/modifier-groups')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_creates_a_group_with_nested_modifiers(): void
    {
        $tenant = $this->createTenant('Menu');
        $this->actingAsMember($tenant);

        $this->postJson('/api/v1/modifier-groups', [
            'name' => 'Término de cocción',
            'selection_type' => 'single',
            'modifiers' => [
                ['name' => 'Jugoso', 'price' => 0, 'is_default' => true],
                ['name' => 'Término medio', 'price' => 0],
            ],
        ])->assertCreated()->assertJsonCount(2, 'data.modifiers');

        $group = ModifierGroup::query()->where('name', 'Término de cocción')->firstOrFail();

        $this->assertDatabaseHas('modifiers', [
            'tenant_id' => $tenant->id,
            'modifier_group_id' => $group->id,
            'name' => 'Jugoso',
        ]);
    }

    public function test_cannot_access_a_group_from_another_tenant(): void
    {
        $alpha = $this->createTenant('Alpha');
        $beta = $this->createTenant('Beta');

        $foreign = ModifierGroup::factory()->create(['tenant_id' => $beta->id]);

        $this->actingAsMember($alpha);

        $this->getJson('/api/v1/modifier-groups/'.$foreign->uuid)->assertNotFound();
    }

    public function test_validates_the_group_payload(): void
    {
        $tenant = $this->createTenant('Validator');
        $this->actingAsMember($tenant);

        $this->postJson('/api/v1/modifier-groups', [
            'name' => '',
            'modifiers' => [['price' => 1]],
        ])->assertStatus(422)->assertJsonValidationErrors(['name', 'modifiers.0.name']);
    }

    public function test_update_replaces_the_nested_modifiers(): void
    {
        $tenant = $this->createTenant('Editor');
        $this->actingAsMember($tenant);

        $group = ModifierGroup::factory()->create(['tenant_id' => $tenant->id]);
        $keep = Modifier::factory()->create([
            'tenant_id' => $tenant->id,
            'modifier_group_id' => $group->id,
            'name' => 'Jugoso',
        ]);
        $drop = Modifier::factory()->create([
            'tenant_id' => $tenant->id,
            'modifier_group_id' => $group->id,
            'name' => 'Seco',
        ]);

        $this->patchJson('/api/v1/modifier-groups/'.$group->uuid, [
            'modifiers' => [
                ['uuid' => $keep->uuid, 'name' => 'Jugoso', 'price' => 0],
                ['name' => 'Término medio', 'price' => 2],
            ],
        ])->assertOk()->assertJsonCount(2, 'data.modifiers');

        $this->assertNotSoftDeleted('modifiers', ['id' => $keep->id]);
        $this->assertSoftDeleted('modifiers', ['id' => $drop->id]);
        $this->assertDatabaseHas('modifiers', [
            'modifier_group_id' => $group->id,
            'name' => 'Término medio',
        ]);
    }
}
