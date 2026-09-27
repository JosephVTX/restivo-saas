<?php

namespace Tests\Feature;

use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\DiningTable;
use App\Models\MenuCategory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithTenancy;
use Tests\TestCase;

class ReportApiTest extends TestCase
{
    use InteractsWithTenancy, RefreshDatabase;

    public function test_owner_receives_the_dashboard_with_expected_keys(): void
    {
        $tenant = $this->createTenant('Reports Keys');

        $this->actingAsMember($tenant, 'owner');

        $response = $this->getJson('/api/v1/reports/dashboard')->assertOk();

        $response->assertJsonStructure(['data' => [
            'range' => ['from', 'to'],
            'sales' => ['total', 'orders', 'average_ticket', 'tips'],
            'sales_by_method',
            'sales_by_hour',
            'top_products',
            'sales_by_category',
            'tables' => ['total', 'occupied', 'available', 'occupancy_rate'],
            'top_waiters',
            'comparison' => ['previous_total', 'change_percentage'],
            'open_orders',
        ]]);

        $this->assertCount(24, $response->json('data.sales_by_hour'));
    }

    public function test_dashboard_computes_kpis_from_paid_orders_and_tables(): void
    {
        $tenant = $this->createTenant('Reports Data');
        $now = CarbonImmutable::now();

        $waiter = User::factory()->create(['name' => 'Ana Mozo']);

        DiningTable::factory()->count(2)->occupied()->create([
            'tenant_id' => $tenant->id,
            'zone_id' => null,
        ]);
        DiningTable::factory()->create([
            'tenant_id' => $tenant->id,
            'zone_id' => null,
        ]);

        $category = MenuCategory::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Entradas',
        ]);
        $product = Product::factory()->create([
            'tenant_id' => $tenant->id,
            'menu_category_id' => $category->id,
            'name' => 'Ceviche',
        ]);

        $first = Order::factory()->paid()->create([
            'tenant_id' => $tenant->id,
            'status' => OrderStatus::Paid,
            'waiter_id' => $waiter->id,
            'total' => 50,
            'tip_total' => 5,
            'paid_at' => $now,
        ]);
        $second = Order::factory()->paid()->create([
            'tenant_id' => $tenant->id,
            'status' => OrderStatus::Paid,
            'total' => 30,
            'tip_total' => 0,
            'paid_at' => $now,
        ]);

        Order::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => OrderStatus::Open,
            'total' => 0,
        ]);

        OrderItem::factory()->create([
            'tenant_id' => $tenant->id,
            'order_id' => $first->id,
            'product_id' => $product->id,
            'product_name' => 'Ceviche',
            'quantity' => 2,
            'unit_price' => 25,
            'line_total' => 50,
            'status' => OrderItemStatus::Delivered,
        ]);
        OrderItem::factory()->create([
            'tenant_id' => $tenant->id,
            'order_id' => $second->id,
            'product_id' => $product->id,
            'product_name' => 'Ceviche',
            'quantity' => 1,
            'unit_price' => 30,
            'line_total' => 30,
            'status' => OrderItemStatus::Void,
        ]);
        OrderItem::factory()->create([
            'tenant_id' => $tenant->id,
            'order_id' => $second->id,
            'product_id' => $product->id,
            'product_name' => 'Ceviche',
            'quantity' => 1,
            'unit_price' => 30,
            'line_total' => 30,
            'status' => OrderItemStatus::Delivered,
        ]);

        Payment::factory()->create([
            'tenant_id' => $tenant->id,
            'order_id' => $first->id,
            'method' => PaymentMethod::Cash,
            'amount' => 50,
            'paid_at' => $now,
        ]);
        Payment::factory()->create([
            'tenant_id' => $tenant->id,
            'order_id' => $second->id,
            'method' => PaymentMethod::Yape,
            'amount' => 30,
            'paid_at' => $now,
        ]);

        $this->actingAsMember($tenant, 'owner');

        $response = $this->getJson('/api/v1/reports/dashboard?period=today')->assertOk();

        $this->assertSame(80.0, (float) $response->json('data.sales.total'));
        $this->assertSame(2, $response->json('data.sales.orders'));
        $this->assertSame(40.0, (float) $response->json('data.sales.average_ticket'));
        $this->assertSame(5.0, (float) $response->json('data.sales.tips'));

        $methods = collect($response->json('data.sales_by_method'))->keyBy('method');
        $this->assertSame(50.0, (float) $methods['cash']['total']);
        $this->assertSame(1, $methods['cash']['count']);
        $this->assertSame(30.0, (float) $methods['yape']['total']);
        $this->assertSame(1, $methods['yape']['count']);

        $this->assertSame('Ceviche', $response->json('data.top_products.0.name'));
        $this->assertSame(3.0, (float) $response->json('data.top_products.0.quantity'));
        $this->assertSame(80.0, (float) $response->json('data.top_products.0.total'));

        $this->assertSame('Entradas', $response->json('data.sales_by_category.0.name'));
        $this->assertSame(80.0, (float) $response->json('data.sales_by_category.0.total'));

        $this->assertSame(3, $response->json('data.tables.total'));
        $this->assertSame(2, $response->json('data.tables.occupied'));
        $this->assertSame(1, $response->json('data.tables.available'));
        $this->assertSame(66.7, (float) $response->json('data.tables.occupancy_rate'));

        $this->assertSame(1, $response->json('data.open_orders'));

        $waiters = collect($response->json('data.top_waiters'))->keyBy('name');
        $this->assertSame(1, $waiters['Ana Mozo']['orders']);
        $this->assertSame(50.0, (float) $waiters['Ana Mozo']['total']);
    }

    public function test_waiter_without_reports_view_is_forbidden(): void
    {
        $tenant = $this->createTenant('Reports Waiter');

        $this->actingAsMember($tenant, 'waiter');

        $this->getJson('/api/v1/reports/dashboard')->assertForbidden();
    }

    public function test_member_without_a_role_is_forbidden(): void
    {
        $tenant = $this->createTenant('Reports No Role');
        $user = User::factory()->create();
        $tenant->memberships()->create(['user_id' => $user->getKey(), 'joined_at' => now()]);

        $this->actingAs($user);
        $this->withSession([config('tenancy.session_key', 'tenant_id') => $tenant->getKey()]);

        $this->getJson('/api/v1/reports/dashboard')->assertForbidden();
    }

    public function test_dashboard_accepts_period_and_explicit_date_range(): void
    {
        $tenant = $this->createTenant('Reports Range');

        $this->actingAsMember($tenant, 'owner');

        $this->getJson('/api/v1/reports/dashboard?period=today')->assertOk();

        $today = CarbonImmutable::now()->toDateString();

        $this->getJson('/api/v1/reports/dashboard?from='.$today.'&to='.$today)->assertOk();
    }
}
