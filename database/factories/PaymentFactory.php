<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'order_id' => Order::factory(),
            'cash_session_id' => null,
            'user_id' => User::factory(),
            'method' => PaymentMethod::Cash,
            'amount' => fake()->randomFloat(2, 5, 200),
            'tip' => 0,
            'received_amount' => null,
            'change_amount' => null,
            'reference' => null,
            'notes' => null,
            'paid_at' => now(),
        ];
    }

    public function yape(): static
    {
        return $this->state(fn (): array => ['method' => PaymentMethod::Yape]);
    }
}
