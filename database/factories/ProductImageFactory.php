<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductImage>
 */
class ProductImageFactory extends Factory
{
    protected $model = ProductImage::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'product_id' => Product::factory(),
            'url' => 'https://res.cloudinary.com/demo/image/upload/restivo/sample.webp',
            'public_id' => 'restivo/sample_'.fake()->unique()->numberBetween(1, 999999),
            'format' => 'webp',
            'width' => 1000,
            'height' => 750,
            'bytes' => 50000,
            'sort_order' => 0,
        ];
    }
}
