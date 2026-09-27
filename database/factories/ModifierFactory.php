<?php

namespace Database\Factories;

use App\Models\Modifier;
use App\Models\ModifierGroup;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Modifier>
 */
class ModifierFactory extends Factory
{
    protected $model = Modifier::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'modifier_group_id' => ModifierGroup::factory(),
            'name' => ucfirst(fake()->unique()->words(2, true)),
            'price' => fake()->randomElement([0, 0, 2, 3, 5]),
            'is_default' => false,
            'sort_order' => fake()->numberBetween(0, 10),
            'is_active' => true,
        ];
    }
}
