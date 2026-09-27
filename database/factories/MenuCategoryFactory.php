<?php

namespace Database\Factories;

use App\Enums\Station;
use App\Models\MenuCategory;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MenuCategory>
 */
class MenuCategoryFactory extends Factory
{
    protected $model = MenuCategory::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->unique()->randomElement([
                'Entradas', 'Fondos', 'Criollos', 'Marinos', 'Bebidas', 'Postres', 'Cafés', 'Pizzas',
            ]),
            'description' => null,
            'station' => Station::Kitchen,
            'sort_order' => fake()->numberBetween(0, 10),
            'is_active' => true,
        ];
    }

    public function drinks(): static
    {
        return $this->state(fn (): array => ['name' => 'Bebidas', 'station' => Station::Bar]);
    }
}
