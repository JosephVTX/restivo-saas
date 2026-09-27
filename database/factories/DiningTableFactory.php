<?php

namespace Database\Factories;

use App\Enums\TableStatus;
use App\Models\DiningTable;
use App\Models\Tenant;
use App\Models\Zone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DiningTable>
 */
class DiningTableFactory extends Factory
{
    protected $model = DiningTable::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'zone_id' => Zone::factory(),
            'name' => 'Mesa '.fake()->unique()->numberBetween(1, 200),
            'capacity' => fake()->randomElement([2, 4, 4, 6, 8]),
            'status' => TableStatus::Available,
            'sort_order' => fake()->numberBetween(0, 20),
            'is_active' => true,
        ];
    }

    public function occupied(): static
    {
        return $this->state(fn (): array => ['status' => TableStatus::Occupied]);
    }
}
