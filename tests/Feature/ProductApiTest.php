<?php

namespace Tests\Feature;

use App\Models\ModifierGroup;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithTenancy;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use InteractsWithTenancy, RefreshDatabase;

    public function test_lists_only_the_products_of_the_active_tenant(): void
    {
        $alpha = $this->createTenant('Alpha');
        $beta = $this->createTenant('Beta');

        Product::factory()->count(2)->create(['tenant_id' => $alpha->id]);
        Product::factory()->count(5)->create(['tenant_id' => $beta->id]);

        $this->actingAsMember($alpha);

        $this->getJson('/api/v1/products')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_creates_a_product_scoped_to_the_active_tenant(): void
    {
        $tenant = $this->createTenant('Creator');
        $this->actingAsMember($tenant);

        $this->postJson('/api/v1/products', [
            'name' => 'Lomo saltado',
            'price' => 32.5,
            'tax_type' => 'gravado',
        ])->assertCreated()->assertJsonPath('data.name', 'Lomo saltado');

        $this->assertDatabaseHas('products', [
            'tenant_id' => $tenant->id,
            'name' => 'Lomo saltado',
        ]);
    }

    public function test_cannot_access_a_product_from_another_tenant(): void
    {
        $alpha = $this->createTenant('Alpha');
        $beta = $this->createTenant('Beta');

        $foreign = Product::factory()->create(['tenant_id' => $beta->id]);

        $this->actingAsMember($alpha);

        $this->getJson('/api/v1/products/'.$foreign->uuid)->assertNotFound();
    }

    public function test_validates_the_product_payload(): void
    {
        $tenant = $this->createTenant('Validator');
        $this->actingAsMember($tenant);

        $this->postJson('/api/v1/products', ['name' => '', 'price' => -1])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'price']);
    }

    public function test_updates_and_deletes_a_product(): void
    {
        $tenant = $this->createTenant('Editor');
        $this->actingAsMember($tenant);

        $product = Product::factory()->create(['tenant_id' => $tenant->id]);

        $this->patchJson('/api/v1/products/'.$product->uuid, ['name' => 'Renombrado', 'price' => 10])
            ->assertOk()
            ->assertJsonPath('data.name', 'Renombrado');

        $this->deleteJson('/api/v1/products/'.$product->uuid)->assertNoContent();

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_syncs_modifier_groups_on_create_and_update(): void
    {
        $tenant = $this->createTenant('Sync');
        $this->actingAsMember($tenant);

        $groupA = ModifierGroup::factory()->create(['tenant_id' => $tenant->id]);
        $groupB = ModifierGroup::factory()->create(['tenant_id' => $tenant->id]);

        $this->postJson('/api/v1/products', [
            'name' => 'Pizza',
            'price' => 25,
            'modifier_groups' => [$groupA->uuid],
        ])->assertCreated();

        $product = Product::query()->where('name', 'Pizza')->firstOrFail();

        $this->assertDatabaseHas('modifier_group_product', [
            'product_id' => $product->id,
            'modifier_group_id' => $groupA->id,
        ]);

        $this->patchJson('/api/v1/products/'.$product->uuid, [
            'modifier_groups' => [$groupB->uuid],
        ])->assertOk();

        $this->assertDatabaseMissing('modifier_group_product', [
            'product_id' => $product->id,
            'modifier_group_id' => $groupA->id,
        ]);
        $this->assertDatabaseHas('modifier_group_product', [
            'product_id' => $product->id,
            'modifier_group_id' => $groupB->id,
        ]);
    }
}
