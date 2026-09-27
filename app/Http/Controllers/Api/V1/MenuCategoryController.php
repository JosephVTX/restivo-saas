<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\App\StoreMenuCategoryRequest;
use App\Http\Requests\App\UpdateMenuCategoryRequest;
use App\Http\Resources\MenuCategoryResource;
use App\Models\MenuCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;

class MenuCategoryController extends ApiController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return MenuCategoryResource::collection($this->indexQuery(
            MenuCategory::class,
            $request,
            filters: [
                AllowedFilter::exact('is_active'),
                $this->searchFilter(['name', 'description']),
            ],
            sorts: ['name', 'sort_order', 'created_at'],
        ));
    }

    public function store(StoreMenuCategoryRequest $request): JsonResponse
    {
        $category = MenuCategory::create($request->validated());
        $category->refresh();

        return (new MenuCategoryResource($category))->response()->setStatusCode(201);
    }

    public function show(string $category): MenuCategoryResource
    {
        return new MenuCategoryResource($this->findByUuid(MenuCategory::class, $category));
    }

    public function update(UpdateMenuCategoryRequest $request, string $category): MenuCategoryResource
    {
        $model = $this->findByUuid(MenuCategory::class, $category);
        $model->update($request->validated());

        return new MenuCategoryResource($model);
    }

    public function destroy(string $category): JsonResponse
    {
        $this->findByUuid(MenuCategory::class, $category)->delete();

        return response()->json(null, 204);
    }
}
