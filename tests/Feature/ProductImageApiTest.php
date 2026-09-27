<?php

namespace Tests\Feature;

use App\Models\PlatformSetting;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\Media\CloudinaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Concerns\InteractsWithTenancy;
use Tests\TestCase;

class ProductImageApiTest extends TestCase
{
    use InteractsWithTenancy, RefreshDatabase;

    private function fakeCloudinary(): void
    {
        $this->mock(CloudinaryService::class, function ($mock): void {
            $mock->shouldReceive('uploadImage')->andReturn([
                'url' => 'https://res.cloudinary.com/demo/image/upload/x.webp',
                'public_id' => 'restivo/x',
                'format' => 'webp',
                'width' => 1000,
                'height' => 750,
                'bytes' => 1200,
            ]);
            $mock->shouldReceive('destroy')->andReturnNull();
            $mock->shouldReceive('configured')->andReturnTrue();
        });
    }

    public function test_uploading_an_image_stores_it_and_sets_the_primary_path(): void
    {
        $this->fakeCloudinary();

        $tenant = $this->createTenant('Images Upload');
        $this->actingAsMember($tenant, 'owner');

        $product = Product::factory()->create(['tenant_id' => $tenant->id]);

        $this->post('/api/v1/products/'.$product->uuid.'/images', [
            'image' => UploadedFile::fake()->image('foto.jpg', 1600, 1200),
        ])
            ->assertCreated()
            ->assertJsonPath('data.url', 'https://res.cloudinary.com/demo/image/upload/x.webp')
            ->assertJsonMissingPath('data.tenant_id');

        $this->assertDatabaseHas('product_images', [
            'tenant_id' => $tenant->id,
            'product_id' => $product->id,
            'url' => 'https://res.cloudinary.com/demo/image/upload/x.webp',
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'image_path' => 'https://res.cloudinary.com/demo/image/upload/x.webp',
        ]);
    }

    public function test_members_without_manage_permission_cannot_upload(): void
    {
        $this->fakeCloudinary();

        $tenant = $this->createTenant('Images Denied');
        $this->actingAsMember($tenant, 'member');

        $product = Product::factory()->create(['tenant_id' => $tenant->id]);

        $this->post('/api/v1/products/'.$product->uuid.'/images', [
            'image' => UploadedFile::fake()->image('foto.jpg', 800, 600),
        ])->assertForbidden();
    }

    public function test_upload_is_rejected_when_the_product_reached_the_limit(): void
    {
        $this->fakeCloudinary();

        $tenant = $this->createTenant('Images Limit');
        $this->actingAsMember($tenant, 'owner');

        PlatformSetting::current()->update(['max_images_per_product' => 1]);

        $product = Product::factory()->create(['tenant_id' => $tenant->id]);
        ProductImage::factory()->create([
            'tenant_id' => $tenant->id,
            'product_id' => $product->id,
        ]);

        $this->post('/api/v1/products/'.$product->uuid.'/images', [
            'image' => UploadedFile::fake()->image('foto.jpg', 800, 600),
        ])->assertStatus(422);
    }

    public function test_destroy_removes_the_image(): void
    {
        $this->fakeCloudinary();

        $tenant = $this->createTenant('Images Delete');
        $this->actingAsMember($tenant, 'owner');

        $product = Product::factory()->create(['tenant_id' => $tenant->id]);
        $image = ProductImage::factory()->create([
            'tenant_id' => $tenant->id,
            'product_id' => $product->id,
        ]);

        $this->deleteJson('/api/v1/product-images/'.$image->uuid)->assertNoContent();

        $this->assertDatabaseMissing('product_images', ['id' => $image->id]);
    }
}
