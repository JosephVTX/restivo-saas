<?php

namespace App\Http\Resources;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Role
 */
class RoleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'label' => __("roles.{$this->name}"),
            'guard_name' => $this->guard_name,
            'permissions' => $this->whenLoaded('permissions', fn () => $this->permissions
                ->map(fn ($permission) => [
                    'name' => $permission->name,
                    'label' => __("permissions.{$permission->name}"),
                ])
                ->values()),
            'users_count' => $this->whenCounted('users'),
        ];
    }
}
