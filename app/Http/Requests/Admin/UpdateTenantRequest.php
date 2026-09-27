<?php

namespace App\Http\Requests\Admin;

use App\Enums\TenantStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', 'alpha_dash', Rule::unique('tenants', 'slug')->ignore($this->route('tenant'))],
            'plan' => ['sometimes', 'nullable', 'string', 'max:100'],
            'locale' => ['sometimes', 'string', 'max:10'],
            'status' => ['sometimes', Rule::enum(TenantStatus::class)],
        ];
    }
}
