<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('cloudinary_enabled')->default(false);
            $table->string('cloudinary_cloud_name')->nullable();
            $table->string('cloudinary_api_key')->nullable();
            $table->text('cloudinary_api_secret')->nullable();
            $table->string('cloudinary_folder')->default('restivo');
            $table->unsignedTinyInteger('max_images_per_product')->default(1);
            $table->unsignedSmallInteger('image_max_width')->default(1000);
            $table->unsignedTinyInteger('webp_quality')->default(75);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_settings');
    }
};
