<?php

namespace Tests\Feature;

use App\Models\MenuCategory;
use App\Models\Modifier;
use App\Models\ModifierGroup;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithTenancy;
use Tests\TestCase;

class MenuApiTest extends TestCase
{
    use InteractsWithTenancy, RefreshDatabase;

    public function test_returns_active_categories_and_products_with_modifiers(): void
    {
        $tenant = $this->createTenant('Menu');
        $other = $this->createTenant('Other');

        $category = MenuCategory::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
            'sort_order' => 1,
        ]);
        MenuCategory::factory()->create(['tenant_id' => $tenant->id, 'is_active' => false]);

        $group = ModifierGroup::factory()->create(['tenant_id' => $tenant->id, 'is_active' => true]);
        Modifier::factory()->create([
            'tenant_id' => $tenant->id,
            'modifier_group_id' => $group->id,
            'is_active' => true,
            'name' => 'Extra queso',
        ]);
        Modifier::factory()->create([
            'tenant_id' => $tenant->id,
            'modifier_group_id' => $group->id,
            'is_active' => false,
        ]);

        $product = Product::factory()->create([
            'tenant_id' => $tenant->id,
            'menu_category_id' => $category->id,
            'is_active' => true,
        ]);
        $product->modifierGroups()->attach($group->id);

        Product::factory()->create(['tenant_id' => $tenant->id, 'is_active' => false]);
        Product::factory()->create(['tenant_id' => $other->id, 'is_active' => true]);

        $this->actingAsMember($tenant, 'waiter');

        $this->getJson('/api/v1/menu')
            ->assertOk()
            ->assertJsonCount(1, 'data.categories')
            ->assertJsonCount(1, 'data.products')
            ->assertJsonPath('data.categories.0.uuid', $category->uuid)
            ->assertJsonPath('data.products.0.uuid', $product->uuid)
            ->assertJsonPath('data.products.0.menu_category_uuid', $category->uuid)
            ->assertJsonCount(1, 'data.products.0.modifier_groups')
            ->assertJsonCount(1, 'data.products.0.modifier_groups.0.modifiers')
            ->assertJsonPath('data.products.0.modifier_groups.0.modifiers.0.name', 'Extra queso');
    }

    public function test_requires_the_menu_view_permission(): void
    {
        $tenant = $this->createTenant('NoPerm');
        $user = User::factory()->create();

        $tenant->memberships()->create([
            'user_id' => $user->getKey(),
            'joined_at' => now(),
        ]);

        $this->actingAs($user);
        $this->withSession([config('tenancy.session_key', 'tenant_id') => $tenant->getKey()]);

        $this->getJson('/api/v1/menu')->assertForbidden();
    }
}
