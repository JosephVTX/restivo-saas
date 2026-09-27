<?php

namespace Tests\Feature;

use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithTenancy;
use Tests\TestCase;

class PlatformSettingApiTest extends TestCase
{
    use InteractsWithTenancy, RefreshDatabase;

    public function test_super_admin_can_read_the_integration_settings(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create());

        $this->getJson('/api/v1/admin/settings/integrations')
            ->assertOk()
            ->assertJsonPath('data.cloudinary_enabled', false)
            ->assertJsonPath('data.max_images_per_product', 1)
            ->assertJsonPath('data.has_secret', false)
            ->assertJsonMissingPath('data.cloudinary_api_secret')
            ->assertJsonMissing(['cloudinary_api_secret']);
    }

    public function test_super_admin_updates_settings_without_exposing_the_secret(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create());

        $this->putJson('/api/v1/admin/settings/integrations', [
            'cloudinary_enabled' => true,
            'cloudinary_cloud_name' => 'demo-cloud',
            'cloudinary_api_key' => '123456789012345',
            'cloudinary_api_secret' => 'super-secret',
            'cloudinary_folder' => 'restivo',
            'max_images_per_product' => 3,
            'image_max_width' => 1200,
            'webp_quality' => 80,
        ])
            ->assertOk()
            ->assertJsonPath('data.cloudinary_enabled', true)
            ->assertJsonPath('data.cloudinary_cloud_name', 'demo-cloud')
            ->assertJsonPath('data.has_secret', true)
            ->assertJsonPath('data.is_configured', true)
            ->assertJsonPath('data.max_images_per_product', 3)
            ->assertJsonMissing(['cloudinary_api_secret'])
            ->assertJsonMissingPath('data.cloudinary_api_secret');

        $settings = PlatformSetting::current();

        $this->assertSame('super-secret', $settings->cloudinary_api_secret);
        $this->assertSame(3, $settings->max_images_per_product);
        $this->assertSame(1200, $settings->image_max_width);
        $this->assertSame(80, $settings->webp_quality);
    }

    public function test_tenant_members_are_forbidden_from_the_settings(): void
    {
        $tenant = $this->createTenant('Settings Denied');
        $this->actingAsMember($tenant, 'owner');

        $this->getJson('/api/v1/admin/settings/integrations')->assertForbidden();
        $this->putJson('/api/v1/admin/settings/integrations', ['cloudinary_enabled' => false])->assertForbidden();
    }

    public function test_connection_test_reports_failure_when_not_configured(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create());

        $this->postJson('/api/v1/admin/settings/integrations/test')
            ->assertOk()
            ->assertJsonPath('data.ok', false);
    }
}
