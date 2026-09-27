<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Platform-wide (central) settings, owned by the super admin. Applies to every
 * tenant — e.g. the shared Cloudinary account used for product images.
 */
#[Fillable([
    'cloudinary_enabled', 'cloudinary_cloud_name', 'cloudinary_api_key',
    'cloudinary_api_secret', 'cloudinary_folder', 'max_images_per_product',
    'image_max_width', 'webp_quality',
])]
class PlatformSetting extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cloudinary_enabled' => 'boolean',
            'cloudinary_api_secret' => 'encrypted',
            'max_images_per_product' => 'integer',
            'image_max_width' => 'integer',
            'webp_quality' => 'integer',
        ];
    }

    /**
     * The single settings row, created with defaults on first access.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'cloudinary_enabled' => false,
            'cloudinary_folder' => 'restivo',
            'max_images_per_product' => 1,
            'image_max_width' => 1000,
            'webp_quality' => 75,
        ]);
    }

    public function cloudinaryConfigured(): bool
    {
        return $this->cloudinary_enabled
            && filled($this->cloudinary_cloud_name)
            && filled($this->cloudinary_api_key)
            && filled($this->cloudinary_api_secret);
    }
}
