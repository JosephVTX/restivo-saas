<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\InteractsWithTenancy;
use Tests\TestCase;

class CustomerApiTest extends TestCase
{
    use InteractsWithTenancy, RefreshDatabase;

    public function test_lists_only_the_customers_of_the_active_tenant(): void
    {
        $alpha = $this->createTenant('Alpha');
        $beta = $this->createTenant('Beta');

        Customer::factory()->count(2)->create(['tenant_id' => $alpha->id]);
        Customer::factory()->count(5)->create(['tenant_id' => $beta->id]);

        $this->actingAsMember($alpha);

        $this->getJson('/api/v1/customers')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_creates_a_customer_scoped_to_the_active_tenant(): void
    {
        $tenant = $this->createTenant('Creator');
        $this->actingAsMember($tenant);

        $this->postJson('/api/v1/customers', [
            'doc_type' => 'dni',
            'doc_number' => '12345678',
            'name' => 'Juan Pérez',
        ])->assertCreated()->assertJsonPath('data.name', 'Juan Pérez');

        $this->assertDatabaseHas('customers', [
            'tenant_id' => $tenant->id,
            'doc_number' => '12345678',
        ]);
    }

    public function test_shows_updates_and_deletes_a_customer(): void
    {
        $tenant = $this->createTenant('Editor');
        $this->actingAsMember($tenant);

        $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);

        $this->getJson('/api/v1/customers/'.$customer->uuid)
            ->assertOk()
            ->assertJsonPath('data.uuid', $customer->uuid);

        $this->patchJson('/api/v1/customers/'.$customer->uuid, ['name' => 'Renombrado'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Renombrado');

        $this->deleteJson('/api/v1/customers/'.$customer->uuid)->assertNoContent();

        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
    }

    public function test_searches_by_name_and_document(): void
    {
        $tenant = $this->createTenant('Search');
        $this->actingAsMember($tenant);

        Customer::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'María Fernanda',
            'doc_number' => '87654321',
        ]);
        Customer::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Otro Cliente']);

        $this->getJson('/api/v1/customers?filter[search]=María')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'María Fernanda');

        $this->getJson('/api/v1/customers?filter[search]=87654321')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.doc_number', '87654321');
    }

    public function test_validates_doc_number_is_unique_per_tenant(): void
    {
        $tenant = $this->createTenant('Unique');
        $this->actingAsMember($tenant);

        Customer::factory()->create([
            'tenant_id' => $tenant->id,
            'doc_type' => 'dni',
            'doc_number' => '12345678',
        ]);

        $this->postJson('/api/v1/customers', [
            'doc_type' => 'dni',
            'doc_number' => '12345678',
            'name' => 'Duplicado',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['doc_number']);
    }

    public function test_validates_the_customer_payload(): void
    {
        $tenant = $this->createTenant('Validator');
        $this->actingAsMember($tenant);

        $this->postJson('/api/v1/customers', ['doc_type' => 'ruc', 'doc_number' => '123', 'name' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'doc_number']);
    }

    public function test_cannot_access_a_customer_from_another_tenant(): void
    {
        $alpha = $this->createTenant('Alpha');
        $beta = $this->createTenant('Beta');

        $foreign = Customer::factory()->create(['tenant_id' => $beta->id]);

        $this->actingAsMember($alpha);

        $this->getJson('/api/v1/customers/'.$foreign->uuid)->assertNotFound();
    }

    public function test_a_member_without_role_cannot_list_customers(): void
    {
        $tenant = $this->createTenant('NoRole');

        $user = User::factory()->create();
        $tenant->memberships()->create([
            'user_id' => $user->getKey(),
            'joined_at' => now(),
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->getKey());

        $this->actingAs($user);
        $this->withSession([config('tenancy.session_key', 'tenant_id') => $tenant->getKey()]);

        $this->getJson('/api/v1/customers')->assertForbidden();
    }
}
