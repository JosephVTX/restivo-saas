<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\TableStatus;
use App\Models\DiningTable;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithTenancy;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use InteractsWithTenancy, RefreshDatabase;

    public function test_opening_an_order_occupies_the_table(): void
    {
        $tenant = $this->createTenant('Orders');
        $zone = Zone::factory()->create(['tenant_id' => $tenant->id]);
        $table = DiningTable::factory()->create([
            'tenant_id' => $tenant->id,
            'zone_id' => $zone->id,
            'status' => TableStatus::Available,
        ]);

        $this->actingAsMember($tenant, 'waiter');

        $this->postJson('/api/v1/orders', [
            'type' => 'dine_in',
            'dining_table' => $table->uuid,
            'guests' => 2,
        ])->assertCreated()->assertJsonPath('data.status', 'open');

        $this->assertDatabaseHas('dining_tables', [
            'id' => $table->id,
            'status' => 'occupied',
        ]);
        $this->assertDatabaseHas('orders', [
            'tenant_id' => $tenant->id,
            'dining_table_id' => $table->id,
            'status' => 'open',
        ]);
    }

    public function test_order_numbers_are_correlative_per_tenant(): void
    {
        $tenant = $this->createTenant('Alpha');
        $other = $this->createTenant('Beta');

        Order::factory()->create(['tenant_id' => $tenant->id, 'number' => 7]);
        Order::factory()->create(['tenant_id' => $other->id, 'number' => 99]);

        $this->actingAsMember($tenant, 'waiter');

        $this->postJson('/api/v1/orders', ['type' => 'takeaway'])
            ->assertCreated()
            ->assertJsonPath('data.number', 8);
    }

    public function test_indexes_orders_with_active_table_and_status_filters(): void
    {
        $tenant = $this->createTenant('Filtered');
        $table = DiningTable::factory()->create(['tenant_id' => $tenant->id]);

        Order::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => OrderStatus::Open,
            'type' => OrderType::DineIn,
            'dining_table_id' => $table->id,
        ]);
        Order::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => OrderStatus::Sent,
            'type' => OrderType::Takeaway,
        ]);
        Order::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => OrderStatus::Paid,
            'type' => OrderType::Takeaway,
        ]);

        $this->actingAsMember($tenant, 'waiter');

        $this->getJson('/api/v1/orders?filter[active]=1')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/v1/orders?filter[status]=paid')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/orders?filter[type]=dine_in')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/orders?filter[table]='.$table->uuid)->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_show_includes_the_order_items(): void
    {
        $tenant = $this->createTenant('Detail');
        $order = Order::factory()->create(['tenant_id' => $tenant->id]);
        $item = OrderItem::factory()->create(['tenant_id' => $tenant->id, 'order_id' => $order->id]);

        $this->actingAsMember($tenant, 'waiter');

        $this->getJson('/api/v1/orders/'.$order->uuid)
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.uuid', $item->uuid);
    }

    public function test_send_marks_the_order_as_sent(): void
    {
        $tenant = $this->createTenant('Sender');
        $order = Order::factory()->create(['tenant_id' => $tenant->id, 'status' => OrderStatus::Open]);
        OrderItem::factory()->create(['tenant_id' => $tenant->id, 'order_id' => $order->id]);

        $this->actingAsMember($tenant, 'waiter');

        $this->postJson('/api/v1/orders/'.$order->uuid.'/send')
            ->assertOk()
            ->assertJsonPath('data.status', 'sent');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'sent']);
    }

    public function test_cancel_changes_the_status_and_frees_the_table(): void
    {
        $tenant = $this->createTenant('Canceller');
        $table = DiningTable::factory()->occupied()->create(['tenant_id' => $tenant->id]);
        $order = Order::factory()->create([
            'tenant_id' => $tenant->id,
            'dining_table_id' => $table->id,
            'status' => OrderStatus::Open,
        ]);

        $this->actingAsMember($tenant, 'owner');

        $this->postJson('/api/v1/orders/'.$order->uuid.'/cancel')
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('dining_tables', ['id' => $table->id, 'status' => 'available']);
    }

    public function test_orders_are_isolated_between_tenants(): void
    {
        $alpha = $this->createTenant('Alpha');
        $beta = $this->createTenant('Beta');

        Order::factory()->count(2)->create(['tenant_id' => $alpha->id]);
        $foreign = Order::factory()->create(['tenant_id' => $beta->id]);

        $this->actingAsMember($alpha, 'waiter');

        $this->getJson('/api/v1/orders')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/v1/orders/'.$foreign->uuid)->assertNotFound();
    }

    public function test_validates_the_order_payload(): void
    {
        $tenant = $this->createTenant('Validator');
        $this->actingAsMember($tenant, 'waiter');

        $this->postJson('/api/v1/orders', ['type' => 'invalid', 'guests' => 99])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['type', 'guests']);
    }
}
