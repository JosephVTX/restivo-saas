<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\TaxType;
use App\Models\Modifier;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithTenancy;
use Tests\TestCase;

class OrderItemApiTest extends TestCase
{
    use InteractsWithTenancy, RefreshDatabase;

    public function test_adding_an_item_computes_the_line_total_and_igv(): void
    {
        $tenant = $this->createTenant('Items');
        $this->actingAsMember($tenant, 'waiter');

        $order = Order::factory()->create(['tenant_id' => $tenant->id, 'status' => OrderStatus::Open]);
        $product = Product::factory()->create([
            'tenant_id' => $tenant->id,
            'price' => 100,
            'tax_type' => TaxType::Gravado,
        ]);

        $this->postJson('/api/v1/orders/'.$order->uuid.'/items', [
            'product' => $product->uuid,
            'quantity' => 2,
        ])
            ->assertCreated()
            ->assertJsonPath('data.line_total', '200.00')
            ->assertJsonPath('data.tax_amount', '30.51')
            ->assertJsonPath('data.modifiers_total', '0.00');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'total' => '200.00']);
    }

    public function test_adding_an_item_with_modifiers_includes_them_in_the_price(): void
    {
        $tenant = $this->createTenant('Modifiers');
        $this->actingAsMember($tenant, 'waiter');

        $order = Order::factory()->create(['tenant_id' => $tenant->id, 'status' => OrderStatus::Open]);
        $product = Product::factory()->create([
            'tenant_id' => $tenant->id,
            'price' => 100,
            'tax_type' => TaxType::Gravado,
        ]);
        $modifier = Modifier::factory()->create(['tenant_id' => $tenant->id, 'price' => 5]);

        $this->postJson('/api/v1/orders/'.$order->uuid.'/items', [
            'product' => $product->uuid,
            'quantity' => 1,
            'modifiers' => [$modifier->uuid],
        ])
            ->assertCreated()
            ->assertJsonCount(1, 'data.modifiers')
            ->assertJsonPath('data.modifiers_total', '5.00')
            ->assertJsonPath('data.line_total', '105.00')
            ->assertJsonPath('data.tax_amount', '16.02');
    }

    public function test_updating_the_quantity_recalculates_the_line(): void
    {
        $tenant = $this->createTenant('Quantity');
        $this->actingAsMember($tenant, 'waiter');

        $order = Order::factory()->create(['tenant_id' => $tenant->id, 'status' => OrderStatus::Open]);
        $product = Product::factory()->create([
            'tenant_id' => $tenant->id,
            'price' => 100,
            'tax_type' => TaxType::Gravado,
        ]);

        $item = $this->postJson('/api/v1/orders/'.$order->uuid.'/items', [
            'product' => $product->uuid,
            'quantity' => 1,
        ])->assertCreated()->json('data');

        $this->patchJson('/api/v1/order-items/'.$item['uuid'], ['quantity' => 3])
            ->assertOk()
            ->assertJsonPath('data.line_total', '300.00');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'total' => '300.00']);
    }

    public function test_voiding_an_item_excludes_it_from_the_total(): void
    {
        $tenant = $this->createTenant('Void');
        $this->actingAsMember($tenant, 'waiter');

        $order = Order::factory()->create(['tenant_id' => $tenant->id, 'status' => OrderStatus::Open]);
        $product = Product::factory()->create([
            'tenant_id' => $tenant->id,
            'price' => 100,
            'tax_type' => TaxType::Gravado,
        ]);

        $item = $this->postJson('/api/v1/orders/'.$order->uuid.'/items', [
            'product' => $product->uuid,
            'quantity' => 1,
        ])->assertCreated()->json('data');

        $this->deleteJson('/api/v1/order-items/'.$item['uuid'])->assertNoContent();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'total' => '0.00']);
        $this->assertDatabaseHas('order_items', ['uuid' => $item['uuid'], 'status' => 'void']);
    }

    public function test_status_can_be_advanced_with_orders_update_or_kitchen_update(): void
    {
        $tenant = $this->createTenant('Kitchen');

        $order = Order::factory()->create(['tenant_id' => $tenant->id, 'status' => OrderStatus::Open]);
        $product = Product::factory()->create(['tenant_id' => $tenant->id, 'price' => 10]);

        $this->actingAsMember($tenant, 'waiter');
        $item = $this->postJson('/api/v1/orders/'.$order->uuid.'/items', [
            'product' => $product->uuid,
            'quantity' => 1,
        ])->assertCreated()->json('data');

        $this->patchJson('/api/v1/order-items/'.$item['uuid'].'/status', ['status' => 'preparing'])
            ->assertOk()
            ->assertJsonPath('data.status', 'preparing');

        $this->actingAsMember($tenant, 'kitchen');
        $this->patchJson('/api/v1/order-items/'.$item['uuid'].'/status', ['status' => 'ready'])
            ->assertOk()
            ->assertJsonPath('data.status', 'ready');
    }

    public function test_adding_an_item_requires_permission(): void
    {
        $tenant = $this->createTenant('NoPerm');
        $order = Order::factory()->create(['tenant_id' => $tenant->id]);
        $product = Product::factory()->create(['tenant_id' => $tenant->id, 'price' => 10]);

        $user = User::factory()->create();
        $tenant->memberships()->create(['user_id' => $user->getKey(), 'joined_at' => now()]);
        $this->actingAs($user);
        $this->withSession([config('tenancy.session_key', 'tenant_id') => $tenant->getKey()]);

        $this->postJson('/api/v1/orders/'.$order->uuid.'/items', [
            'product' => $product->uuid,
            'quantity' => 1,
        ])->assertForbidden();
    }

    public function test_validates_the_item_payload_and_tenant_scope(): void
    {
        $tenant = $this->createTenant('Validator');
        $other = $this->createTenant('Other');
        $this->actingAsMember($tenant, 'waiter');

        $order = Order::factory()->create(['tenant_id' => $tenant->id, 'status' => OrderStatus::Open]);
        $foreign = Product::factory()->create(['tenant_id' => $other->id, 'price' => 10]);

        $this->postJson('/api/v1/orders/'.$order->uuid.'/items', [
            'product' => $foreign->uuid,
            'quantity' => 0,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['product', 'quantity']);
    }
}
