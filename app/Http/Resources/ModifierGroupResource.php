<?php

namespace App\Http\Resources;

use App\Models\ModifierGroup;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ModifierGroup
 */
class ModifierGroupResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'selection_type' => $this->selection_type->value,
            'selection_type_label' => $this->selection_type->label(),
            'is_required' => $this->is_required,
            'min_selections' => $this->min_selections,
            'max_selections' => $this->max_selections,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'modifiers' => ModifierResource::collection($this->whenLoaded('modifiers')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
