<?php

namespace Database\Factories;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->unique()->catchPhrase(),
            'description' => fake()->paragraph(),
            'status' => ProjectStatus::Draft,
            'meta' => [],
            'due_date' => fake()->optional()->dateTimeBetween('now', '+3 months'),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (): array => ['status' => ProjectStatus::Active]);
    }

    public function archived(): static
    {
        return $this->state(fn (): array => ['status' => ProjectStatus::Archived]);
    }
}
