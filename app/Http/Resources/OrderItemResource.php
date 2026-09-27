<?php

namespace App\Http\Resources;

use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OrderItem
 */
class OrderItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'product_uuid' => $this->whenLoaded('product', fn (): ?string => $this->product?->uuid, null),
            'product_name' => $this->product_name,
            'tax_type' => $this->tax_type->value,
            'tax_type_label' => $this->tax_type->label(),
            'station' => $this->station->value,
            'station_label' => $this->station->label(),
            'unit_price' => $this->unit_price,
            'modifiers_total' => $this->modifiers_total,
            'quantity' => $this->quantity,
            'line_total' => $this->line_total,
            'tax_amount' => $this->tax_amount,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'notes' => $this->notes,
            'sort_order' => $this->sort_order,
            'modifiers' => $this->whenLoaded('modifiers', fn () => OrderItemModifierResource::collection($this->modifiers), []),
        ];
    }
}
