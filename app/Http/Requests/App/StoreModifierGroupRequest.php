<?php

namespace App\Http\Requests\App;

use App\Enums\ModifierSelectionType;
use App\Models\ModifierGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreModifierGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ModifierGroup::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'selection_type' => ['sometimes', Rule::enum(ModifierSelectionType::class)],
            'is_required' => ['sometimes', 'boolean'],
            'min_selections' => ['sometimes', 'integer', 'min:0'],
            'max_selections' => ['sometimes', 'integer', 'min:0'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'modifiers' => ['sometimes', 'array'],
            'modifiers.*.uuid' => ['sometimes', 'string'],
            'modifiers.*.name' => ['required_with:modifiers', 'string', 'max:255'],
            'modifiers.*.price' => ['sometimes', 'numeric', 'min:0'],
            'modifiers.*.is_default' => ['sometimes', 'boolean'],
            'modifiers.*.sort_order' => ['sometimes', 'integer', 'min:0'],
            'modifiers.*.is_active' => ['sometimes', 'boolean'],
        ];
    }
}
