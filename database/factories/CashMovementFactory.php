<?php

namespace Database\Factories;

use App\Enums\CashMovementType;
use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashMovement>
 */
class CashMovementFactory extends Factory
{
    protected $model = CashMovement::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'cash_session_id' => CashSession::factory(),
            'payment_id' => null,
            'user_id' => User::factory(),
            'type' => CashMovementType::In,
            'amount' => fake()->randomFloat(2, 1, 200),
            'concept' => fake()->sentence(3),
            'notes' => null,
        ];
    }

    public function out(): static
    {
        return $this->state(fn (): array => ['type' => CashMovementType::Out]);
    }
}
