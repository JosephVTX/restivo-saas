<?php

namespace Database\Factories;

use App\Enums\CashSessionStatus;
use App\Models\CashSession;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashSession>
 */
class CashSessionFactory extends Factory
{
    protected $model = CashSession::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'closed_by_id' => null,
            'status' => CashSessionStatus::Open,
            'opening_amount' => fake()->randomFloat(2, 0, 300),
            'expected_amount' => null,
            'closing_amount' => null,
            'difference' => null,
            'notes' => null,
            'opened_at' => now(),
            'closed_at' => null,
        ];
    }

    public function closed(): static
    {
        return $this->state(fn (): array => [
            'status' => CashSessionStatus::Closed,
            'closed_at' => now(),
        ]);
    }
}
