<?php

namespace App\Http\Requests\App;

use App\Enums\Role;
use App\Models\Membership;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('invite', Membership::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'role' => ['required', Rule::in(Role::values())],
            'job_title' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
