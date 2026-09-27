<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\Zone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Zone>
 */
class ZoneFactory extends Factory
{
    protected $model = Zone::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->randomElement(['Salón', 'Terraza', 'Barra', 'Segundo piso']),
            'sort_order' => fake()->numberBetween(0, 5),
            'is_active' => true,
        ];
    }
}
