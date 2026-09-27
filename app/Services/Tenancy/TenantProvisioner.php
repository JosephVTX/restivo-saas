<?php

namespace App\Services\Tenancy;

use App\Enums\Role as RoleEnum;
use App\Models\Tenant;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates the default roles and permissions for a tenant.
 *
 * Permissions are global (shared by every tenant); roles are scoped to the
 * tenant through spatie's teams feature (team_foreign_key = tenant_id).
 */
final class TenantProvisioner
{
    /**
     * @var array<int, string>
     */
    public const PERMISSIONS = [
        // Carta / menú
        'menu.view',
        'menu.manage',
        // Salón (zonas y mesas)
        'tables.view',
        'tables.manage',
        // Pedidos
        'orders.view',
        'orders.create',
        'orders.update',
        'orders.cancel',
        // Cocina (KDS)
        'kitchen.view',
        'kitchen.update',
        // Caja y pagos
        'cash.view',
        'cash.manage',
        'payments.create',
        // Comprobantes
        'documents.view',
        'documents.create',
        // Clientes
        'customers.view',
        'customers.manage',
        // Reportes / KPIs
        'reports.view',
        // Facturación electrónica
        'billing.manage',
        'members.view',
        'members.invite',
        'members.remove',
        'roles.view',
        'roles.manage',
        'settings.view',
        'settings.manage',
    ];

    /**
     * @var array<string, array<int, string>>
     */
    private const ROLE_PERMISSIONS = [
        RoleEnum::Owner->value => self::PERMISSIONS,
        RoleEnum::Admin->value => [
            'menu.view', 'menu.manage',
            'tables.view', 'tables.manage',
            'orders.view', 'orders.create', 'orders.update', 'orders.cancel',
            'kitchen.view', 'kitchen.update',
            'cash.view', 'cash.manage', 'payments.create',
            'documents.view', 'documents.create',
            'customers.view', 'customers.manage',
            'reports.view',
            'members.view', 'members.invite', 'members.remove',
            'roles.view', 'settings.view',
        ],
        RoleEnum::Cashier->value => [
            'menu.view',
            'tables.view',
            'orders.view', 'orders.create', 'orders.update',
            'kitchen.view',
            'cash.view', 'cash.manage', 'payments.create',
            'documents.view', 'documents.create',
            'customers.view', 'customers.manage',
            'reports.view',
        ],
        RoleEnum::Waiter->value => [
            'menu.view',
            'tables.view',
            'orders.view', 'orders.create', 'orders.update',
            'kitchen.view',
            'payments.create',
            'customers.view',
        ],
        RoleEnum::Kitchen->value => [
            'menu.view',
            'orders.view',
            'kitchen.view', 'kitchen.update',
        ],
        RoleEnum::Member->value => [
            'menu.view',
            'tables.view',
            'orders.view', 'orders.create', 'orders.update',
            'kitchen.view',
            'members.view', 'settings.view',
        ],
    ];

    public function __construct(private readonly PermissionRegistrar $registrar) {}

    /**
     * Ensure the global permission catalogue exists.
     */
    public static function syncPermissions(): void
    {
        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
    }

    public function provision(Tenant $tenant): void
    {
        self::syncPermissions();

        $this->registrar->setPermissionsTeamId($tenant->getKey());

        foreach (self::ROLE_PERMISSIONS as $role => $permissions) {
            $model = Role::findOrCreate($role, 'web');
            $model->syncPermissions($permissions);
        }
    }
}
