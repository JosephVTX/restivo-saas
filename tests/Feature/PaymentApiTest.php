<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\DiningTable;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithTenancy;
use Tests\TestCase;

class PaymentApiTest extends TestCase
{
    use InteractsWithTenancy, RefreshDatabase;

    public function test_a_full_payment_closes_the_order_and_frees_the_table(): void
    {
        $tenant = $this->createTenant('Payment Full');
        $table = DiningTable::factory()->occupied()->create(['tenant_id' => $tenant->id]);
        $order = Order::factory()->create([
            'tenant_id' => $tenant->id,
            'dining_table_id' => $table->id,
            'status' => OrderStatus::Open,
            'total' => 50,
        ]);

        $this->actingAsMember($tenant, 'cashier');

        $this->postJson('/api/v1/orders/'.$order->uuid.'/payments', [
            'method' => 'cash',
            'amount' => 50,
            'received_amount' => 60,
        ])
            ->assertCreated()
            ->assertJsonPath('data.change_amount', '10.00')
            ->assertJsonPath('data.order.uuid', $order->uuid);

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'paid']);
        $this->assertDatabaseHas('dining_tables', ['id' => $table->id, 'status' => 'available']);
    }

    public function test_a_split_payment_only_closes_the_order_when_fully_covered(): void
    {
        $tenant = $this->createTenant('Payment Split');
        $order = Order::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => OrderStatus::Open,
            'total' => 100,
        ]);

        $this->actingAsMember($tenant, 'cashier');

        $this->postJson('/api/v1/orders/'.$order->uuid.'/payments', [
            'method' => 'cash',
            'amount' => 40,
        ])->assertCreated();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'open', 'paid_total' => 40]);

        $this->getJson('/api/v1/orders/'.$order->uuid)
            ->assertOk()
            ->assertJsonPath('data.remaining', 60);

        $this->postJson('/api/v1/orders/'.$order->uuid.'/payments', [
            'method' => 'yape',
            'amount' => 60,
        ])->assertCreated();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'paid']);
    }

    public function test_cash_payment_computes_change_from_received_amount(): void
    {
        $tenant = $this->createTenant('Payment Change');
        $order = Order::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => OrderStatus::Open,
            'total' => 35,
        ]);

        $this->actingAsMember($tenant, 'cashier');

        $this->postJson('/api/v1/orders/'.$order->uuid.'/payments', [
            'method' => 'cash',
            'amount' => 35,
            'received_amount' => 50,
        ])
            ->assertCreated()
            ->assertJsonPath('data.change_amount', '15.00')
            ->assertJsonPath('data.received_amount', '50.00');
    }

    public function test_tips_are_added_to_the_order_tip_total(): void
    {
        $tenant = $this->createTenant('Payment Tip');
        $order = Order::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => OrderStatus::Open,
            'total' => 20,
        ]);

        $this->actingAsMember($tenant, 'cashier');

        $this->postJson('/api/v1/orders/'.$order->uuid.'/payments', [
            'method' => 'yape',
            'amount' => 20,
            'tip' => 3,
        ])->assertCreated();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'paid_total' => 20, 'tip_total' => 3]);
    }

    public function test_remaining_is_correct_after_a_partial_payment(): void
    {
        $tenant = $this->createTenant('Payment Remaining');
        $order = Order::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => OrderStatus::Open,
            'total' => 100,
        ]);

        $this->actingAsMember($tenant, 'cashier');

        $this->postJson('/api/v1/orders/'.$order->uuid.'/payments', [
            'method' => 'cash',
            'amount' => 30,
        ])->assertCreated();

        $this->getJson('/api/v1/orders/'.$order->uuid)
            ->assertOk()
            ->assertJsonPath('data.paid_total', '30.00')
            ->assertJsonPath('data.remaining', 70);
    }

    public function test_a_paid_order_cannot_be_paid_again(): void
    {
        $tenant = $this->createTenant('Payment Done');
        $order = Order::factory()->paid()->create(['tenant_id' => $tenant->id, 'total' => 50]);

        $this->actingAsMember($tenant, 'cashier');

        $this->postJson('/api/v1/orders/'.$order->uuid.'/payments', [
            'method' => 'cash',
            'amount' => 50,
        ])->assertStatus(422);
    }

    public function test_payment_permissions_are_enforced(): void
    {
        $tenant = $this->createTenant('Payment Denied');
        $order = Order::factory()->create(['tenant_id' => $tenant->id, 'total' => 50]);

        $this->actingAsMember($tenant, 'member');

        $this->postJson('/api/v1/orders/'.$order->uuid.'/payments', [
            'method' => 'cash',
            'amount' => 50,
        ])->assertForbidden();
    }
}
