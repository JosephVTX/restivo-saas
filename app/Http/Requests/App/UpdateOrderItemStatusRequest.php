<?php

namespace App\Http\Requests\App;

use App\Enums\OrderItemStatus;
use App\Models\OrderItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderItemStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('advance', $this->item()) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(OrderItemStatus::class)],
        ];
    }

    private function item(): OrderItem
    {
        return OrderItem::query()->where('uuid', $this->route('item'))->firstOrFail();
    }
}
