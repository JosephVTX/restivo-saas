<?php

namespace App\Http\Requests\App;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->project()) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'status' => ['sometimes', Rule::enum(ProjectStatus::class)],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'meta' => ['sometimes', 'nullable', 'array'],
        ];
    }

    private function project(): Project
    {
        return Project::query()->where('uuid', $this->route('project'))->firstOrFail();
    }
}
