<?php

namespace Database\Factories;

use App\Enums\OrderItemStatus;
use App\Enums\Station;
use App\Enums\TaxType;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $price = (float) fake()->randomFloat(2, 6, 45);

        return [
            'tenant_id' => Tenant::factory(),
            'order_id' => Order::factory(),
            'product_id' => null,
            'product_name' => ucfirst(fake()->unique()->words(2, true)),
            'tax_type' => TaxType::Gravado,
            'station' => Station::Kitchen,
            'unit_price' => $price,
            'modifiers_total' => 0,
            'quantity' => 1,
            'line_total' => $price,
            'tax_amount' => round($price - $price / 1.18, 2),
            'status' => OrderItemStatus::Pending,
            'notes' => null,
            'sort_order' => fake()->numberBetween(0, 10),
            'sent_at' => null,
            'ready_at' => null,
        ];
    }
}
