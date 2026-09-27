<?php

namespace App\Http\Requests\App;

use App\Enums\TableStatus;
use App\Models\DiningTable;
use App\Models\Zone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDiningTableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', DiningTable::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->resolveZoneUuid();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'zone_id' => ['sometimes', 'nullable', Rule::exists('zones', 'id')->where('tenant_id', current_tenant_id())],
            'name' => ['required', 'string', 'max:255'],
            'capacity' => ['sometimes', 'integer', 'min:1'],
            'status' => ['sometimes', Rule::enum(TableStatus::class)],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    private function resolveZoneUuid(): void
    {
        $zone = $this->input('zone_id');

        if ($zone === '') {
            $this->merge(['zone_id' => null]);

            return;
        }

        if (is_string($zone) && ! ctype_digit($zone)) {
            $this->merge(['zone_id' => Zone::query()->where('uuid', $zone)->value('id') ?? $zone]);
        }
    }
}
