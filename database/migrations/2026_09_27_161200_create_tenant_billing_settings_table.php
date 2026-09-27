<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_billing_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->boolean('enabled')->default(false);
            $table->string('ruc', 11)->nullable();
            $table->string('business_name')->nullable();
            $table->string('trade_name')->nullable();
            $table->string('address')->nullable();
            $table->string('ubigeo', 6)->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('sol_user')->nullable();
            $table->text('sol_password')->nullable();
            $table->string('certificate_path')->nullable();
            $table->text('certificate_password')->nullable();
            $table->string('mode')->default('beta');
            $table->string('boleta_series', 10)->default('B001');
            $table->string('factura_series', 10)->default('F001');
            $table->string('legend')->nullable();
            $table->decimal('igv_rate', 5, 4)->default(0.1800);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_billing_settings');
    }
};
