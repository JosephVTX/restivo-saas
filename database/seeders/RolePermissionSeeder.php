<?php

namespace Database\Seeders;

use App\Services\Tenancy\TenantProvisioner;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        TenantProvisioner::syncPermissions();
    }
}
