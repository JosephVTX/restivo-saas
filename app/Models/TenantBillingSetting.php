<?php

namespace App\Models;

use App\Enums\BillingMode;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Database\Factories\TenantBillingSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Per-tenant electronic invoicing configuration (SUNAT / Greenter). Secrets are
 * stored encrypted at rest.
 */
#[Fillable([
    'enabled', 'ruc', 'business_name', 'trade_name', 'address', 'ubigeo',
    'email', 'phone', 'sol_user', 'sol_password', 'certificate_path',
    'certificate_password', 'mode', 'boleta_series', 'factura_series',
    'legend', 'igv_rate',
])]
class TenantBillingSetting extends Model
{
    /** @use HasFactory<TenantBillingSettingFactory> */
    use BelongsToTenant, HasFactory, HasUuid, SoftDeletes;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'enabled' => false,
        'mode' => 'beta',
        'boleta_series' => 'B001',
        'factura_series' => 'F001',
        'igv_rate' => 0.18,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'mode' => BillingMode::class,
            'igv_rate' => 'decimal:4',
            'sol_password' => 'encrypted',
            'certificate_password' => 'encrypted',
        ];
    }

    public function hasCertificate(): bool
    {
        return is_string($this->certificate_path) && is_file($this->certificate_path);
    }

    public function isElectronicConfigured(): bool
    {
        return (bool) $this->enabled
            && $this->ruc !== null
            && $this->sol_user !== null
            && $this->sol_password !== null
            && $this->certificate_path !== null;
    }

    public function igvRate(): float
    {
        return (float) ($this->igv_rate ?? config('restivo.igv_rate'));
    }
}
