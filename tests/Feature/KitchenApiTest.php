<?php

namespace Tests\Feature;

use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Enums\Station;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithTenancy;
use Tests\TestCase;

class KitchenApiTest extends TestCase
{
    use InteractsWithTenancy, RefreshDatabase;

    public function test_lists_active_kitchen_and_bar_items_of_active_orders(): void
    {
        $tenant = $this->createTenant('Kitchen');
        $order = Order::factory()->create(['tenant_id' => $tenant->id, 'status' => OrderStatus::Sent]);

        OrderItem::factory()->create([
            'tenant_id' => $tenant->id,
            'order_id' => $order->id,
            'status' => OrderItemStatus::Pending,
            'station' => Station::Kitchen,
        ]);
        OrderItem::factory()->create([
            'tenant_id' => $tenant->id,
            'order_id' => $order->id,
            'status' => OrderItemStatus::Preparing,
            'station' => Station::Bar,
        ]);
        OrderItem::factory()->create([
            'tenant_id' => $tenant->id,
            'order_id' => $order->id,
            'status' => OrderItemStatus::Ready,
            'station' => Station::Kitchen,
        ]);

        $this->actingAsMember($tenant, 'kitchen');

        $this->getJson('/api/v1/kitchen')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data' => [[
                'uuid', 'order_uuid', 'order_number', 'order_type', 'order_type_label',
                'table_name', 'waiter_name', 'product_name', 'quantity',
                'station', 'station_label', 'status', 'status_label',
                'notes', 'modifiers', 'sent_at', 'created_at', 'minutes_waiting',
            ]]]);
    }

    public function test_excludes_items_outside_the_station_status_and_open_order_scope(): void
    {
        $tenant = $this->createTenant('Scoped');
        $active = Order::factory()->create(['tenant_id' => $tenant->id, 'status' => OrderStatus::Open]);
        $paid = Order::factory()->paid()->create(['tenant_id' => $tenant->id]);

        OrderItem::factory()->create([
            'tenant_id' => $tenant->id,
            'order_id' => $active->id,
            'status' => OrderItemStatus::Pending,
            'station' => Station::Kitchen,
        ]);
        OrderItem::factory()->create([
            'tenant_id' => $tenant->id,
            'order_id' => $active->id,
            'status' => OrderItemStatus::Delivered,
            'station' => Station::Kitchen,
        ]);
        OrderItem::factory()->create([
            'tenant_id' => $tenant->id,
            'order_id' => $active->id,
            'status' => OrderItemStatus::Void,
            'station' => Station::Kitchen,
        ]);
        OrderItem::factory()->create([
            'tenant_id' => $tenant->id,
            'order_id' => $active->id,
            'status' => OrderItemStatus::Pending,
            'station' => Station::None,
        ]);
        OrderItem::factory()->create([
            'tenant_id' => $tenant->id,
            'order_id' => $paid->id,
            'status' => OrderItemStatus::Pending,
            'station' => Station::Kitchen,
        ]);

        $this->actingAsMember($tenant, 'kitchen');

        $this->getJson('/api/v1/kitchen')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_filters_the_board_by_station(): void
    {
        $tenant = $this->createTenant('Bar');
        $order = Order::factory()->create(['tenant_id' => $tenant->id, 'status' => OrderStatus::Sent]);

        OrderItem::factory()->create([
            'tenant_id' => $tenant->id,
            'order_id' => $order->id,
            'station' => Station::Kitchen,
        ]);
        $bar = OrderItem::factory()->create([
            'tenant_id' => $tenant->id,
            'order_id' => $order->id,
            'station' => Station::Bar,
        ]);

        $this->actingAsMember($tenant, 'kitchen');

        $this->getJson('/api/v1/kitchen?filter[station]=bar')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.uuid', $bar->uuid)
            ->assertJsonPath('data.0.station', 'bar');
    }

    public function test_kitchen_board_is_isolated_between_tenants(): void
    {
        $alpha = $this->createTenant('Alpha');
        $beta = $this->createTenant('Beta');

        $alphaOrder = Order::factory()->create(['tenant_id' => $alpha->id, 'status' => OrderStatus::Sent]);
        OrderItem::factory()->create([
            'tenant_id' => $alpha->id,
            'order_id' => $alphaOrder->id,
            'station' => Station::Kitchen,
        ]);

        $betaOrder = Order::factory()->create(['tenant_id' => $beta->id, 'status' => OrderStatus::Sent]);
        OrderItem::factory()->create([
            'tenant_id' => $beta->id,
            'order_id' => $betaOrder->id,
            'station' => Station::Kitchen,
        ]);

        $this->actingAsMember($alpha, 'kitchen');

        $this->getJson('/api/v1/kitchen')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_members_without_kitchen_view_are_forbidden(): void
    {
        $tenant = $this->createTenant('NoPerm');
        $user = User::factory()->create();
        $tenant->memberships()->create(['user_id' => $user->getKey(), 'joined_at' => now()]);

        $this->actingAs($user);
        $this->withSession([config('tenancy.session_key', 'tenant_id') => $tenant->getKey()]);

        $this->getJson('/api/v1/kitchen')->assertForbidden();
    }
}
