<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\Concerns\InteractsWithTenancy;
use Tests\TestCase;

class WaiterOrdersPageTest extends TestCase
{
    use InteractsWithTenancy, RefreshDatabase;

    public function test_waiters_see_their_focused_orders_page(): void
    {
        $tenant = $this->createTenant('Orders Waiter');
        $this->actingAsMember($tenant, 'waiter');

        $this->get(route('app.orders'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('app/Orders/Waiter')
                ->has('paymentMethodOptions'));
    }

    public function test_owners_see_the_full_orders_page(): void
    {
        $tenant = $this->createTenant('Orders Owner');
        $this->actingAsMember($tenant, 'owner');

        $this->get(route('app.orders'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('app/Orders/Index'));
    }

    public function test_a_waiter_can_filter_orders_to_their_own(): void
    {
        $tenant = $this->createTenant('Orders Mine');
        $waiter = $this->actingAsMember($tenant, 'waiter');
        $other = $this->createMember($tenant, 'waiter');

        Order::factory()->create(['tenant_id' => $tenant->id, 'waiter_id' => $waiter->id]);
        Order::factory()->create(['tenant_id' => $tenant->id, 'waiter_id' => $other->id]);

        $this->getJson('/api/v1/orders?filter[mine]=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.waiter.uuid', $waiter->uuid);
    }
}
