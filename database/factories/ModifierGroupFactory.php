<?php

namespace Database\Factories;

use App\Enums\ModifierSelectionType;
use App\Models\ModifierGroup;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ModifierGroup>
 */
class ModifierGroupFactory extends Factory
{
    protected $model = ModifierGroup::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->unique()->randomElement([
                'Término de cocción', 'Tamaño', 'Extras', 'Sin ingredientes', 'Salsas',
            ]),
            'selection_type' => ModifierSelectionType::Single,
            'is_required' => false,
            'min_selections' => 0,
            'max_selections' => 1,
            'sort_order' => fake()->numberBetween(0, 10),
            'is_active' => true,
        ];
    }
}
