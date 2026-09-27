<?php

namespace App\Http\Requests\App;

use App\Models\OrderItem;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->item()) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'quantity' => ['sometimes', 'required', 'numeric', 'min:0.5', 'max:99'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    private function item(): OrderItem
    {
        return OrderItem::query()->where('uuid', $this->route('item'))->firstOrFail();
    }
}
