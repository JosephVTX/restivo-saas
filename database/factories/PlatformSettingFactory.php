<?php

namespace Database\Factories;

use App\Models\PlatformSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlatformSetting>
 */
class PlatformSettingFactory extends Factory
{
    protected $model = PlatformSetting::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cloudinary_enabled' => false,
            'cloudinary_cloud_name' => null,
            'cloudinary_api_key' => null,
            'cloudinary_api_secret' => null,
            'cloudinary_folder' => 'restivo',
            'max_images_per_product' => 1,
            'image_max_width' => 1000,
            'webp_quality' => 75,
        ];
    }

    public function configured(): static
    {
        return $this->state(fn (): array => [
            'cloudinary_enabled' => true,
            'cloudinary_cloud_name' => 'demo-cloud',
            'cloudinary_api_key' => '123456789012345',
            'cloudinary_api_secret' => 'demo-secret',
        ]);
    }
}
