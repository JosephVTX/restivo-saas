<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\App\StoreProductRequest;
use App\Http\Requests\App\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\MenuCategory;
use App\Models\ModifierGroup;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Spatie\QueryBuilder\AllowedFilter;

class ProductController extends ApiController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return ProductResource::collection($this->indexQuery(
            Product::class,
            $request,
            filters: [
                AllowedFilter::exact('is_active'),
                AllowedFilter::exact('is_available'),
                AllowedFilter::callback('category', function (Builder $query, mixed $value): void {
                    $query->whereIn('menu_category_id', MenuCategory::query()->where('uuid', $value)->pluck('id'));
                }),
                $this->searchFilter(['name', 'description', 'sku']),
            ],
            sorts: ['name', 'price', 'sort_order', 'created_at'],
            with: ['category', 'modifierGroups', 'images'],
        ));
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = Product::create(Arr::except($request->validated(), ['modifier_groups']));
        $product->refresh();

        $this->syncModifierGroups($product, $request->input('modifier_groups'));

        return (new ProductResource($product->load('category', 'modifierGroups', 'images')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(string $product): ProductResource
    {
        return new ProductResource(
            $this->findByUuid(Product::class, $product)->load('category', 'modifierGroups', 'images'),
        );
    }

    public function update(UpdateProductRequest $request, string $product): ProductResource
    {
        $model = $this->findByUuid(Product::class, $product);
        $model->update(Arr::except($request->validated(), ['modifier_groups']));

        $this->syncModifierGroups($model, $request->input('modifier_groups'));

        return new ProductResource($model->load('category', 'modifierGroups', 'images'));
    }

    public function destroy(string $product): JsonResponse
    {
        $this->findByUuid(Product::class, $product)->delete();

        return response()->json(null, 204);
    }

    /**
     * Replace the product's modifier groups from the given uuids, preserving
     * their order through the pivot `sort_order`.
     *
     * @param  array<int, string>|null  $uuids
     */
    private function syncModifierGroups(Product $product, ?array $uuids): void
    {
        if ($uuids === null) {
            return;
        }

        $ids = ModifierGroup::query()->whereIn('uuid', $uuids)->pluck('id')->values();

        $product->modifierGroups()->sync(
            $ids->mapWithKeys(fn (int $id, int $index): array => [$id => ['sort_order' => $index]])->all(),
        );
    }
}
