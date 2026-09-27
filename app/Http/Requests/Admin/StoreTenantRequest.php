<?php

namespace App\Http\Requests\Admin;

use App\Enums\PlanDuration;
use App\Enums\TenantStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreTenantRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', 'alpha_dash', 'unique:tenants,slug'],
            'plan' => ['sometimes', 'nullable', 'string', 'max:100'],
            'locale' => ['sometimes', 'string', 'max:10'],
            'status' => ['sometimes', Rule::enum(TenantStatus::class)],
            'duration' => ['sometimes', 'nullable', Rule::enum(PlanDuration::class)],

            // The client owner that receives access to the new tenant.
            'owner_name' => ['required', 'string', 'max:255'],
            'owner_email' => ['required', 'string', 'email', 'max:255'],
            'owner_password' => ['sometimes', 'nullable', 'string', Password::defaults()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'owner_name' => 'owner name',
            'owner_email' => 'owner email',
            'owner_password' => 'owner password',
        ];
    }
}
