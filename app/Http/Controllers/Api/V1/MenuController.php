<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\MenuCategory;
use App\Models\Modifier;
use App\Models\ModifierGroup;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MenuController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('menu.view') ?? false, 403);

        $categories = MenuCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $products = Product::query()
            ->where('is_active', true)
            ->with([
                'category',
                'images',
                'modifierGroups' => fn ($query) => $query->where('is_active', true),
                'modifierGroups.modifiers' => fn ($query) => $query->where('is_active', true),
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => [
                'categories' => $categories->map(fn (MenuCategory $category): array => [
                    'uuid' => $category->uuid,
                    'name' => $category->name,
                    'color' => $category->color,
                    'sort_order' => $category->sort_order,
                ])->all(),
                'products' => $products->map(fn (Product $product): array => [
                    'uuid' => $product->uuid,
                    'name' => $product->name,
                    'description' => $product->description,
                    'price' => $product->price,
                    'is_available' => $product->is_available,
                    'tax_type' => $product->tax_type->value,
                    'station' => $product->station->value,
                    'menu_category_uuid' => $product->category?->uuid,
                    'modifier_groups' => $product->modifierGroups->map(fn (ModifierGroup $group): array => [
                        'uuid' => $group->uuid,
                        'name' => $group->name,
                        'selection_type' => $group->selection_type->value,
                        'is_required' => $group->is_required,
                        'min_selections' => $group->min_selections,
                        'max_selections' => $group->max_selections,
                        'modifiers' => $group->modifiers->map(fn (Modifier $modifier): array => [
                            'uuid' => $modifier->uuid,
                            'name' => $modifier->name,
                            'price' => $modifier->price,
                            'is_default' => $modifier->is_default,
                        ])->all(),
                    ])->all(),
                ])->all(),
            ],
        ]);
    }
}
