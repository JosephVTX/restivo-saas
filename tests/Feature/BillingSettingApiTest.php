<?php

namespace Tests\Feature;

use App\Models\TenantBillingSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\InteractsWithTenancy;
use Tests\TestCase;

class BillingSettingApiTest extends TestCase
{
    use InteractsWithTenancy, RefreshDatabase;

    public function test_show_creates_and_returns_defaults_without_secrets(): void
    {
        $tenant = $this->createTenant('Billing Show');

        $this->actingAsMember($tenant, 'owner');

        $this->getJson('/api/v1/billing/settings')
            ->assertOk()
            ->assertJsonPath('data.enabled', false)
            ->assertJsonPath('data.boleta_series', 'B001')
            ->assertJsonPath('data.mode', 'beta')
            ->assertJsonMissingPath('data.sol_password')
            ->assertJsonMissingPath('data.certificate_password');

        $this->assertDatabaseHas('tenant_billing_settings', ['tenant_id' => $tenant->id]);
    }

    public function test_update_requires_billing_permission(): void
    {
        $tenant = $this->createTenant('Billing Denied');

        $this->actingAsMember($tenant, 'member');

        $this->putJson('/api/v1/billing/settings', ['enabled' => true])->assertForbidden();
    }

    public function test_update_saves_the_configuration(): void
    {
        $tenant = $this->createTenant('Billing Update');

        $this->actingAsMember($tenant, 'owner');

        $this->putJson('/api/v1/billing/settings', [
            'enabled' => true,
            'ruc' => '20123456789',
            'business_name' => 'RESTAURANTE DEMO SAC',
            'mode' => 'production',
            'boleta_series' => 'B002',
            'igv_rate' => 0.18,
        ])
            ->assertOk()
            ->assertJsonPath('data.enabled', true)
            ->assertJsonPath('data.ruc', '20123456789')
            ->assertJsonPath('data.mode', 'production');

        $this->assertDatabaseHas('tenant_billing_settings', [
            'tenant_id' => $tenant->id,
            'enabled' => true,
            'ruc' => '20123456789',
            'mode' => 'production',
            'boleta_series' => 'B002',
        ]);
    }

    public function test_certificate_upload_stores_the_file_and_sets_the_path(): void
    {
        Storage::fake('local');

        $tenant = $this->createTenant('Billing Certificate');

        $this->actingAsMember($tenant, 'owner');

        $this->post('/api/v1/billing/settings/certificate', [
            'certificate' => UploadedFile::fake()->create('certificado.pem', 10, 'text/plain'),
        ])->assertOk();

        $settings = TenantBillingSetting::query()->where('tenant_id', $tenant->id)->firstOrFail();

        $this->assertNotNull($settings->certificate_path);
        $this->assertStringEndsWith('.pem', $settings->certificate_path);
        Storage::disk('local')->assertExists('billing/certificates/'.$tenant->id.'-'.$settings->uuid.'.pem');
    }
}
