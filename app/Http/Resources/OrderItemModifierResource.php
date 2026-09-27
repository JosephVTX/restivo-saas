<?php

namespace App\Http\Resources;

use App\Models\OrderItemModifier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OrderItemModifier
 */
class OrderItemModifierResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->modifier_name,
            'price' => $this->price,
            'quantity' => $this->quantity,
        ];
    }
}
