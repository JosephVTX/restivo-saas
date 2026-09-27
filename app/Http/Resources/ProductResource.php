<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Product
 */
class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'sku' => $this->sku,
            'price' => $this->price,
            'cost' => $this->cost,
            'tax_type' => $this->tax_type->value,
            'tax_type_label' => $this->tax_type->label(),
            'station' => $this->station->value,
            'station_label' => $this->station->label(),
            'unit' => $this->unit,
            'is_available' => $this->is_available,
            'track_stock' => $this->track_stock,
            'stock' => $this->stock,
            'image_path' => $this->image_path,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'menu_category_id' => $this->menu_category_id,
            'category' => new MenuCategoryResource($this->whenLoaded('category')),
            'images' => ProductImageResource::collection($this->whenLoaded('images')),
            'modifier_groups' => ModifierGroupResource::collection($this->whenLoaded('modifierGroups')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
