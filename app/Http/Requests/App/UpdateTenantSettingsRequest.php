<?php

namespace App\Http\Requests\App;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', tenant_context()->require()) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'locale' => ['sometimes', 'string', 'in:en,es'],
            'settings' => ['sometimes', 'array'],
            'settings.timezone' => ['sometimes', 'nullable', 'string', 'max:64'],
            'settings.currency' => ['sometimes', 'nullable', 'string', 'max:8'],
        ];
    }
}
