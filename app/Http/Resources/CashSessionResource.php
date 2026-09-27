<?php

namespace App\Http\Resources;

use App\Models\CashSession;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CashSession
 */
class CashSessionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'opening_amount' => $this->opening_amount,
            'expected_amount' => $this->expected_amount,
            'closing_amount' => $this->closing_amount,
            'difference' => $this->difference,
            'notes' => $this->notes,
            'opened_at' => $this->opened_at?->toIso8601String(),
            'closed_at' => $this->closed_at?->toIso8601String(),
            'opened_by' => $this->whenLoaded('user', fn (): ?string => $this->user?->name, null),
            'closed_by' => $this->whenLoaded('closedBy', fn (): ?string => $this->closedBy?->name, null),
            'payments_count' => $this->whenCounted('payments'),
            'movements_count' => $this->whenCounted('movements'),
            'movements' => CashMovementResource::collection($this->whenLoaded('movements')),
        ];
    }
}
