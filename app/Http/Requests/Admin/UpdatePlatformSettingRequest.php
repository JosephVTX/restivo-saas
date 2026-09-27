<?php

namespace App\Http\Requests\Admin;

use App\Models\PlatformSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdatePlatformSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_super_admin ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'cloudinary_enabled' => ['sometimes', 'boolean'],
            'cloudinary_cloud_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'cloudinary_api_key' => ['sometimes', 'nullable', 'string', 'max:255'],
            'cloudinary_api_secret' => ['sometimes', 'nullable', 'string', 'max:255'],
            'cloudinary_folder' => ['sometimes', 'nullable', 'string', 'max:255'],
            'max_images_per_product' => ['sometimes', 'integer', 'min:1', 'max:10'],
            'image_max_width' => ['sometimes', 'integer', 'min:200', 'max:2000'],
            'webp_quality' => ['sometimes', 'integer', 'min:30', 'max:100'],
        ];
    }

    /**
     * Enabling Cloudinary requires the full credential set, either supplied in
     * this request or already stored on the platform settings row.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->boolean('cloudinary_enabled')) {
                    return;
                }

                $settings = PlatformSetting::current();

                $present = [
                    'cloudinary_cloud_name' => $this->input('cloudinary_cloud_name') ?: $settings->cloudinary_cloud_name,
                    'cloudinary_api_key' => $this->input('cloudinary_api_key') ?: $settings->cloudinary_api_key,
                    'cloudinary_api_secret' => $this->filled('cloudinary_api_secret') ? $this->input('cloudinary_api_secret') : $settings->cloudinary_api_secret,
                ];

                foreach ($present as $field => $value) {
                    if (blank($value)) {
                        $validator->errors()->add($field, 'Este campo es obligatorio cuando Cloudinary está activo.');
                    }
                }
            },
        ];
    }
}
