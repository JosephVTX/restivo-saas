<?php

namespace App\Http\Resources;

use App\Models\DiningTable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DiningTable
 */
class DiningTableResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'zone_id' => $this->zone_id,
            'name' => $this->name,
            'capacity' => $this->capacity,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'zone' => new ZoneResource($this->whenLoaded('zone')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
