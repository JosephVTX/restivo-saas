<?php

namespace Database\Factories;

use App\Enums\Station;
use App\Enums\TaxType;
use App\Models\MenuCategory;
use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'menu_category_id' => MenuCategory::factory(),
            'name' => ucfirst(fake()->unique()->words(2, true)),
            'description' => fake()->optional()->sentence(),
            'sku' => null,
            'price' => fake()->randomFloat(2, 6, 45),
            'cost' => null,
            'tax_type' => TaxType::Gravado,
            'station' => Station::Kitchen,
            'unit' => 'unidad',
            'is_available' => true,
            'track_stock' => false,
            'stock' => null,
            'sort_order' => fake()->numberBetween(0, 20),
            'is_active' => true,
        ];
    }

    public function unavailable(): static
    {
        return $this->state(fn (): array => ['is_available' => false]);
    }
}
