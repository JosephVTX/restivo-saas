<?php

namespace App\Http\Resources;

use App\Models\TenantBillingSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TenantBillingSetting
 */
class BillingSettingResource extends JsonResource
{
    /**
     * Secrets (sol_password, certificate_password) are never exposed.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'enabled' => $this->enabled,
            'ruc' => $this->ruc,
            'business_name' => $this->business_name,
            'trade_name' => $this->trade_name,
            'address' => $this->address,
            'ubigeo' => $this->ubigeo,
            'email' => $this->email,
            'phone' => $this->phone,
            'sol_user' => $this->sol_user,
            'mode' => $this->mode?->value,
            'mode_label' => $this->mode?->label(),
            'boleta_series' => $this->boleta_series,
            'factura_series' => $this->factura_series,
            'legend' => $this->legend,
            'igv_rate' => $this->igv_rate,
            'has_certificate' => $this->hasCertificate(),
            'is_electronic_configured' => $this->isElectronicConfigured(),
        ];
    }
}
