<?php

namespace Database\Factories;

use App\Enums\BillingMode;
use App\Models\Tenant;
use App\Models\TenantBillingSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TenantBillingSetting>
 */
class TenantBillingSettingFactory extends Factory
{
    protected $model = TenantBillingSetting::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'enabled' => false,
            'ruc' => null,
            'business_name' => null,
            'trade_name' => null,
            'address' => null,
            'ubigeo' => null,
            'email' => null,
            'phone' => null,
            'sol_user' => null,
            'sol_password' => null,
            'certificate_path' => null,
            'certificate_password' => null,
            'mode' => BillingMode::Beta,
            'boleta_series' => 'B001',
            'factura_series' => 'F001',
            'legend' => null,
            'igv_rate' => 0.18,
        ];
    }

    public function enabled(): static
    {
        return $this->state(fn (): array => [
            'enabled' => true,
            'ruc' => '20123456789',
            'business_name' => 'RESTAURANTE DEMO SAC',
            'trade_name' => 'Restaurante Demo',
            'address' => 'Av. Siempre Viva 123, Lima',
            'ubigeo' => '150101',
            'sol_user' => 'MODDATOS',
            'sol_password' => 'moddatos',
            'certificate_path' => '/tmp/demo.pem',
            'mode' => BillingMode::Beta,
        ]);
    }
}
