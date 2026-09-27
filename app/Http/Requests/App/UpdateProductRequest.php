<?php

namespace App\Http\Requests\App;

use App\Enums\Station;
use App\Enums\TaxType;
use App\Models\MenuCategory;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->product()) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $category = $this->input('menu_category_id');

        if ($category === '') {
            $this->merge(['menu_category_id' => null]);

            return;
        }

        if (is_string($category) && ! ctype_digit($category)) {
            $this->merge(['menu_category_id' => MenuCategory::query()->where('uuid', $category)->value('id') ?? $category]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:255'],
            'sku' => ['sometimes', 'nullable', 'string', 'max:255'],
            'menu_category_id' => ['sometimes', 'nullable', Rule::exists('menu_categories', 'id')->where('tenant_id', current_tenant_id())],
            'price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'cost' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'tax_type' => ['sometimes', Rule::enum(TaxType::class)],
            'station' => ['sometimes', Rule::enum(Station::class)],
            'unit' => ['sometimes', 'string', 'max:20'],
            'is_available' => ['sometimes', 'boolean'],
            'track_stock' => ['sometimes', 'boolean'],
            'stock' => ['sometimes', 'nullable', 'numeric'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'modifier_groups' => ['sometimes', 'array'],
            'modifier_groups.*' => ['string', Rule::exists('modifier_groups', 'uuid')->where('tenant_id', current_tenant_id())],
        ];
    }

    private function product(): Product
    {
        return Product::query()->where('uuid', $this->route('product'))->firstOrFail();
    }
}
