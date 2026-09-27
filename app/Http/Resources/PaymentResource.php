<?php

namespace App\Http\Resources;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Payment
 */
class PaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'method' => $this->method->value,
            'method_label' => $this->method->label(),
            'amount' => $this->amount,
            'tip' => $this->tip,
            'received_amount' => $this->received_amount,
            'change_amount' => $this->change_amount,
            'reference' => $this->reference,
            'notes' => $this->notes,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'order' => $this->whenLoaded('order', fn (): ?array => $this->order === null ? null : [
                'uuid' => $this->order->uuid,
                'number' => $this->order->number,
            ], null),
            'user' => $this->whenLoaded('user', fn (): ?string => $this->user?->name, null),
        ];
    }
}
