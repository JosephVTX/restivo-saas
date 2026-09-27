<?php

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Order
 */
class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'number' => $this->number,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'guests' => $this->guests,
            'subtotal' => $this->subtotal,
            'tax_total' => $this->tax_total,
            'discount_total' => $this->discount_total,
            'tip_total' => $this->tip_total,
            'total' => $this->total,
            'paid_total' => $this->paid_total,
            'remaining' => round((float) $this->total - (float) $this->paid_total, 2),
            'notes' => $this->notes,
            'opened_at' => $this->opened_at?->toIso8601String(),
            'paid_at' => $this->paid_at?->toIso8601String(),
            'closed_at' => $this->closed_at?->toIso8601String(),
            'dining_table' => $this->whenLoaded('diningTable', fn (): ?array => $this->diningTable === null ? null : [
                'uuid' => $this->diningTable->uuid,
                'name' => $this->diningTable->name,
            ], null),
            'waiter' => $this->whenLoaded('waiter', fn (): ?array => $this->waiter === null ? null : [
                'uuid' => $this->waiter->uuid,
                'name' => $this->waiter->name,
            ], null),
            'items_count' => $this->whenCounted('items'),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
