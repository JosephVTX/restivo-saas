<?php

namespace App\Http\Controllers\App;

use App\Enums\Station;
use App\Enums\TaxType;
use App\Http\Controllers\Controller;
use App\Models\MenuCategory;
use App\Models\ModifierGroup;
use App\Models\PlatformSetting;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(): Response
    {
        $settings = PlatformSetting::current();

        return Inertia::render('app/Menu/Products', [
            'tax_types' => enum_options(TaxType::class),
            'stations' => enum_options(Station::class),
            'maxImagesPerProduct' => $settings->max_images_per_product,
            'cloudinaryConfigured' => $settings->cloudinaryConfigured(),
            'categories' => MenuCategory::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['uuid', 'name'])
                ->map(fn (MenuCategory $category): array => [
                    'uuid' => $category->uuid,
                    'name' => $category->name,
                ])
                ->all(),
            'modifier_groups' => ModifierGroup::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['uuid', 'name'])
                ->map(fn (ModifierGroup $group): array => [
                    'uuid' => $group->uuid,
                    'name' => $group->name,
                ])
                ->all(),
        ]);
    }
}
