<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Models\CashSession;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithTenancy;
use Tests\TestCase;

class CashSessionApiTest extends TestCase
{
    use InteractsWithTenancy, RefreshDatabase;

    public function test_opening_a_session_returns_created_and_only_one_can_be_open(): void
    {
        $tenant = $this->createTenant('Cash Open');
        $this->actingAsMember($tenant, 'cashier');

        $this->postJson('/api/v1/cash/sessions', ['opening_amount' => 100, 'notes' => 'Turno mañana'])
            ->assertCreated()
            ->assertJsonPath('data.status', 'open')
            ->assertJsonPath('data.opening_amount', '100.00');

        $this->postJson('/api/v1/cash/sessions', ['opening_amount' => 50])
            ->assertStatus(422);

        $this->assertDatabaseCount('cash_sessions', 1);
    }

    public function test_current_returns_the_open_session_with_summary(): void
    {
        $tenant = $this->createTenant('Cash Current');
        $session = CashSession::factory()->create([
            'tenant_id' => $tenant->id,
            'opening_amount' => 80,
        ]);

        $this->actingAsMember($tenant, 'cashier');

        $this->getJson('/api/v1/cash/session')
            ->assertOk()
            ->assertJsonPath('data.uuid', $session->uuid)
            ->assertJsonPath('summary.opening_amount', 80)
            ->assertJsonPath('summary.expected_cash', 80);
    }

    public function test_current_returns_null_when_there_is_no_open_session(): void
    {
        $tenant = $this->createTenant('Cash Empty');
        CashSession::factory()->closed()->create(['tenant_id' => $tenant->id]);

        $this->actingAsMember($tenant, 'cashier');

        $this->getJson('/api/v1/cash/session')
            ->assertOk()
            ->assertJsonPath('data', null)
            ->assertJsonPath('summary', null);
    }

    public function test_closing_computes_expected_and_difference_including_cash_payments(): void
    {
        $tenant = $this->createTenant('Cash Close');
        $session = CashSession::factory()->create([
            'tenant_id' => $tenant->id,
            'opening_amount' => 50,
        ]);
        $order = Order::factory()->create(['tenant_id' => $tenant->id, 'total' => 20]);
        Payment::factory()->create([
            'tenant_id' => $tenant->id,
            'order_id' => $order->id,
            'cash_session_id' => $session->id,
            'method' => PaymentMethod::Cash,
            'amount' => 20,
        ]);

        $this->actingAsMember($tenant, 'cashier');

        $this->postJson('/api/v1/cash/sessions/'.$session->uuid.'/close', ['closing_amount' => 100])
            ->assertOk()
            ->assertJsonPath('data.status', 'closed')
            ->assertJsonPath('data.expected_amount', '70.00')
            ->assertJsonPath('data.difference', '30.00');

        $this->assertDatabaseHas('cash_sessions', [
            'id' => $session->id,
            'status' => 'closed',
            'closing_amount' => 100,
        ]);
    }

    public function test_movements_affect_expected_cash(): void
    {
        $tenant = $this->createTenant('Cash Movements');
        $session = CashSession::factory()->create([
            'tenant_id' => $tenant->id,
            'opening_amount' => 100,
        ]);

        $this->actingAsMember($tenant, 'cashier');

        $this->postJson('/api/v1/cash/sessions/'.$session->uuid.'/movements', [
            'type' => 'in',
            'amount' => 50,
            'concept' => 'Aporte de caja',
        ])->assertCreated()->assertJsonPath('data.type', 'in');

        $this->postJson('/api/v1/cash/sessions/'.$session->uuid.'/movements', [
            'type' => 'out',
            'amount' => 30,
            'concept' => 'Compra de insumos',
        ])->assertCreated()->assertJsonPath('data.type', 'out');

        $this->getJson('/api/v1/cash/session')
            ->assertOk()
            ->assertJsonPath('summary.movements_in', 50)
            ->assertJsonPath('summary.movements_out', 30)
            ->assertJsonPath('summary.expected_cash', 120);
    }

    public function test_cash_permissions_are_enforced(): void
    {
        $tenant = $this->createTenant('Cash Denied');
        $this->actingAsMember($tenant, 'member');

        $this->getJson('/api/v1/cash/session')->assertForbidden();
        $this->postJson('/api/v1/cash/sessions', ['opening_amount' => 10])->assertForbidden();
    }
}
