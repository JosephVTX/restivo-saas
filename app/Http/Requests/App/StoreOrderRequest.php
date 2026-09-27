<?php

namespace App\Http\Requests\App;

use App\Enums\OrderType;
use App\Models\DiningTable;
use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Order::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $table = $this->input('dining_table');

        if ($table === '') {
            $this->merge(['dining_table' => null, 'dining_table_id' => null]);

            return;
        }

        if (is_string($table) && ! ctype_digit($table)) {
            $this->merge([
                'dining_table_id' => DiningTable::query()->where('uuid', $table)->value('id'),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in([
                OrderType::DineIn->value,
                OrderType::Takeaway->value,
                OrderType::Delivery->value,
            ])],
            'dining_table' => ['sometimes', 'nullable', 'string', Rule::exists('dining_tables', 'uuid')->where('tenant_id', current_tenant_id())],
            'dining_table_id' => ['sometimes', 'nullable', 'integer', Rule::exists('dining_tables', 'id')->where('tenant_id', current_tenant_id())],
            'guests' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:50'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
