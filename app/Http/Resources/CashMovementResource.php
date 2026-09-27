<?php

namespace App\Http\Resources;

use App\Models\CashMovement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CashMovement
 */
class CashMovementResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'amount' => $this->amount,
            'concept' => $this->concept,
            'notes' => $this->notes,
            'user' => $this->whenLoaded('user', fn (): ?string => $this->user?->name, null),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
