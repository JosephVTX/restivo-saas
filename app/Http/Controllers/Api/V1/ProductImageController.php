<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\App\StoreProductImageRequest;
use App\Http\Resources\ProductImageResource;
use App\Models\PlatformSetting;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\Media\CloudinaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use RuntimeException;

class ProductImageController extends ApiController
{
    public function store(StoreProductImageRequest $request, string $product): JsonResponse
    {
        $model = $this->findByUuid(Product::class, $product);

        if ($model->images()->count() >= PlatformSetting::current()->max_images_per_product) {
            abort(422, 'Este producto ya alcanzó el máximo de imágenes permitidas.');
        }

        $cloudinary = app(CloudinaryService::class);

        if (! $cloudinary->configured()) {
            abort(422, 'Cloudinary no está configurado. Pídele al administrador que agregue las credenciales.');
        }

        try {
            $uploaded = $cloudinary->uploadImage($request->file('image')->get());
        } catch (RuntimeException $exception) {
            abort(422, $exception->getMessage());
        }

        $image = $model->images()->create([
            'url' => $uploaded['url'],
            'public_id' => $uploaded['public_id'],
            'format' => $uploaded['format'],
            'width' => $uploaded['width'],
            'height' => $uploaded['height'],
            'bytes' => $uploaded['bytes'],
            'sort_order' => ((int) $model->images()->max('sort_order')) + 1,
        ]);

        if (blank($model->image_path)) {
            $model->update(['image_path' => $image->url]);
        }

        return (new ProductImageResource($image))
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(Request $request, string $image): Response
    {
        abort_unless($request->user()?->can('menu.manage') ?? false, 403);

        $model = $this->findByUuid(ProductImage::class, $image);

        $product = $model->product;

        app(CloudinaryService::class)->destroy($model->public_id);

        $model->delete();

        if ($product !== null && $product->image_path === $model->url) {
            $product->update(['image_path' => $product->images()->value('url')]);
        }

        return response()->noContent();
    }
}
