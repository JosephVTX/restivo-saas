<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\Order;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'number' => fake()->unique()->numberBetween(1, 100000),
            'type' => OrderType::DineIn,
            'status' => OrderStatus::Open,
            'dining_table_id' => null,
            'waiter_id' => null,
            'guests' => fake()->numberBetween(1, 4),
            'subtotal' => 0,
            'tax_total' => 0,
            'discount_total' => 0,
            'tip_total' => 0,
            'total' => 0,
            'paid_total' => 0,
            'notes' => null,
            'opened_at' => now(),
            'paid_at' => null,
            'closed_at' => null,
        ];
    }

    public function takeaway(): static
    {
        return $this->state(fn (): array => [
            'type' => OrderType::Takeaway,
            'dining_table_id' => null,
        ]);
    }

    public function sent(): static
    {
        return $this->state(fn (): array => ['status' => OrderStatus::Sent]);
    }

    public function paid(): static
    {
        return $this->state(fn (): array => [
            'status' => OrderStatus::Paid,
            'paid_at' => now(),
            'closed_at' => now(),
        ]);
    }
}
