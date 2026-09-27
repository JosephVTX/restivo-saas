<?php

namespace App\Http\Requests\App;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->order()) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'guests' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:50'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    private function order(): Order
    {
        return Order::query()->where('uuid', $this->route('order'))->firstOrFail();
    }
}
