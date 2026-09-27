<?php

namespace App\Http\Requests\App;

use App\Models\Modifier;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $product = $this->input('product');

        if (is_string($product) && ! ctype_digit($product)) {
            $this->merge([
                'product_id' => Product::query()->where('uuid', $product)->value('id') ?? $product,
            ]);
        }

        $modifiers = $this->input('modifiers');

        if (is_array($modifiers)) {
            $ids = Modifier::query()->whereIn('uuid', $modifiers)->pluck('id', 'uuid');

            $this->merge([
                'modifiers' => array_map(
                    fn (mixed $uuid): mixed => is_string($uuid) ? ($ids[$uuid] ?? $uuid) : $uuid,
                    $modifiers,
                ),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'product' => ['required', 'string', Rule::exists('products', 'uuid')->where('tenant_id', current_tenant_id())],
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('tenant_id', current_tenant_id())],
            'quantity' => ['required', 'numeric', 'min:0.5', 'max:99'],
            'modifiers' => ['sometimes', 'nullable', 'array'],
            'modifiers.*' => ['integer', Rule::exists('modifiers', 'id')->where('tenant_id', current_tenant_id())],
            'notes' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
