<?php

namespace Database\Seeders;

use App\Models\PlatformSetting;
use Illuminate\Database\Seeder;

class PlatformSettingSeeder extends Seeder
{
    public function run(): void
    {
        $cloudName = env('CLOUDINARY_CLOUD_NAME');
        $apiKey = env('CLOUDINARY_API_KEY');
        $apiSecret = env('CLOUDINARY_API_SECRET');

        PlatformSetting::query()->updateOrCreate(['id' => 1], [
            'cloudinary_enabled' => filled($cloudName) && filled($apiKey) && filled($apiSecret),
            'cloudinary_cloud_name' => $cloudName ?: null,
            'cloudinary_api_key' => $apiKey ?: null,
            'cloudinary_api_secret' => $apiSecret ?: null,
            'cloudinary_folder' => env('CLOUDINARY_FOLDER', 'restivo'),
            'max_images_per_product' => (int) env('CLOUDINARY_MAX_IMAGES_PER_PRODUCT', 2),
            'image_max_width' => (int) env('CLOUDINARY_IMAGE_MAX_WIDTH', 700),
            'webp_quality' => (int) env('CLOUDINARY_WEBP_QUALITY', 75),
        ]);
    }
}
