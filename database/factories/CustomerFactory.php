<?php

namespace Database\Factories;

use App\Enums\IdentityDocumentType;
use App\Models\Customer;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'doc_type' => IdentityDocumentType::Dni,
            'doc_number' => (string) fake()->unique()->numerify('########'),
            'name' => fake()->name(),
            'email' => fake()->optional()->safeEmail(),
            'phone' => fake()->optional()->numerify('9########'),
            'address' => fake()->optional()->address(),
            'notes' => null,
            'is_active' => true,
        ];
    }

    public function withRuc(): static
    {
        return $this->state(fn (): array => [
            'doc_type' => IdentityDocumentType::Ruc,
            'doc_number' => (string) fake()->unique()->numerify('20#########'),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
