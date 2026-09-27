<?php

namespace App\Http\Resources;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Tenant
 */
class TenantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'slug' => $this->slug,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'plan' => $this->plan,
            'locale' => $this->locale,
            'settings' => $this->settings,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'days_until_expiry' => $this->daysUntilExpiry(),
            'is_expiring_soon' => $this->isExpiringSoon(),
            'has_expired' => $this->hasExpired(),
            'suspended_at' => $this->suspended_at?->toIso8601String(),
            'members_count' => $this->whenCounted('memberships'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
