<?php

namespace App\Http\Resources;

use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OrderItem
 */
class KitchenItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $reference = $this->sent_at ?? $this->created_at;

        return [
            'uuid' => $this->uuid,
            'order_uuid' => $this->order->uuid,
            'order_number' => $this->order->number,
            'order_type' => $this->order->type->value,
            'order_type_label' => $this->order->type->label(),
            'table_name' => $this->order->diningTable?->name,
            'waiter_name' => $this->order->waiter?->name,
            'product_name' => $this->product_name,
            'quantity' => $this->quantity,
            'station' => $this->station->value,
            'station_label' => $this->station->label(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'notes' => $this->notes,
            'modifiers' => $this->whenLoaded('modifiers', fn () => $this->modifiers->map(fn ($modifier): array => [
                'name' => $modifier->modifier_name,
                'quantity' => $modifier->quantity,
            ])->values(), []),
            'sent_at' => $this->sent_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'minutes_waiting' => $reference ? max(0, (int) now()->diffInMinutes($reference)) : 0,
        ];
    }
}
