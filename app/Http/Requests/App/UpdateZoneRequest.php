<?php

namespace App\Http\Requests\App;

use App\Models\Zone;
use Illuminate\Foundation\Http\FormRequest;

class UpdateZoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->zone()) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    private function zone(): Zone
    {
        return Zone::query()->where('uuid', $this->route('zone'))->firstOrFail();
    }
}
