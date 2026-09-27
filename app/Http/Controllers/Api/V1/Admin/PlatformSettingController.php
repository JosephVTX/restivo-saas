<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Admin\UpdatePlatformSettingRequest;
use App\Http\Resources\PlatformSettingResource;
use App\Models\PlatformSetting;
use App\Services\Media\CloudinaryService;
use Illuminate\Http\JsonResponse;

class PlatformSettingController extends ApiController
{
    public function show(): JsonResponse
    {
        return (new PlatformSettingResource(PlatformSetting::current()))
            ->response()
            ->setStatusCode(200);
    }

    public function update(UpdatePlatformSettingRequest $request): PlatformSettingResource
    {
        $settings = PlatformSetting::current();

        $data = $request->safe()->only([
            'cloudinary_enabled',
            'cloudinary_cloud_name',
            'cloudinary_api_key',
            'cloudinary_folder',
            'max_images_per_product',
            'image_max_width',
            'webp_quality',
        ]);

        if ($request->filled('cloudinary_api_secret')) {
            $data['cloudinary_api_secret'] = $request->string('cloudinary_api_secret')->toString();
        }

        $settings->update($data);

        return new PlatformSettingResource($settings);
    }

    public function test(): JsonResponse
    {
        return response()->json([
            'data' => app(CloudinaryService::class)->ping(),
        ]);
    }
}
