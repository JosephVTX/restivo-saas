<?php

namespace App\Http\Resources;

use App\Models\PlatformSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PlatformSetting
 */
class PlatformSettingResource extends JsonResource
{
    /**
     * The Cloudinary API secret is never exposed; only whether one is stored.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'cloudinary_enabled' => $this->cloudinary_enabled,
            'cloudinary_cloud_name' => $this->cloudinary_cloud_name,
            'cloudinary_api_key' => $this->cloudinary_api_key,
            'cloudinary_folder' => $this->cloudinary_folder,
            'max_images_per_product' => $this->max_images_per_product,
            'image_max_width' => $this->image_max_width,
            'webp_quality' => $this->webp_quality,
            'has_secret' => filled($this->cloudinary_api_secret),
            'is_configured' => $this->cloudinaryConfigured(),
        ];
    }
}
