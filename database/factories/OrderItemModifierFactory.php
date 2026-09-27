<?php

namespace Database\Factories;

use App\Models\OrderItem;
use App\Models\OrderItemModifier;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItemModifier>
 */
class OrderItemModifierFactory extends Factory
{
    protected $model = OrderItemModifier::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'order_item_id' => OrderItem::factory(),
            'modifier_id' => null,
            'modifier_name' => ucfirst(fake()->words(2, true)),
            'price' => fake()->randomFloat(2, 0, 5),
            'quantity' => 1,
        ];
    }
}
