<?php

namespace App\Services\Tenancy;

use App\Models\Membership;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

/**
 * Grants a user access to a tenant.
 *
 * Registration is closed: only a super admin creates tenants and their
 * owner (or additional members) through this service.
 */
final class AccessGranter
{
    /**
     * @return array{user: User, membership: Membership, temporary_password: string|null}
     */
    public function grant(Tenant $tenant, string $name, string $email, string $role, ?string $password = null, ?string $jobTitle = null): array
    {
        $user = User::query()->firstOrNew(['email' => $email]);
        $temporaryPassword = null;

        if (! $user->exists) {
            $temporaryPassword = $password ?? Str::password(16);

            $user->forceFill([
                'name' => $name !== '' ? $name : Str::before($email, '@'),
                'password' => $temporaryPassword,
                'email_verified_at' => now(),
            ])->save();
        }

        $membership = $tenant->memberships()->firstOrCreate(
            ['user_id' => $user->getKey()],
            ['job_title' => $jobTitle, 'joined_at' => now()],
        );

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->getKey());
        $user->syncRoles([$role]);

        return [
            'user' => $user,
            'membership' => $membership,
            'temporary_password' => $temporaryPassword,
        ];
    }
}
