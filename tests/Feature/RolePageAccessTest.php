<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\InteractsWithTenancy;
use Tests\TestCase;

class RolePageAccessTest extends TestCase
{
    use InteractsWithTenancy, RefreshDatabase;

    /**
     * @return array<string, array{0: string, 1: int}>
     */
    public static function waiterPages(): array
    {
        return [
            'orders allowed' => ['/app/orders', 200],
            'pos allowed' => ['/app/pos', 200],
            'tables allowed' => ['/app/tables', 200],
            'menu allowed' => ['/app/menu/products', 200],
            'customers allowed' => ['/app/customers', 200],
            'kitchen allowed' => ['/app/kitchen', 200],
            'cash denied' => ['/app/cash', 403],
            'zones denied' => ['/app/zones', 403],
            'members denied' => ['/app/members', 403],
            'roles denied' => ['/app/roles', 403],
            'settings denied' => ['/app/settings', 403],
            'billing denied' => ['/app/settings/billing', 403],
        ];
    }

    #[DataProvider('waiterPages')]
    public function test_waiter_page_access(string $path, int $status): void
    {
        $tenant = $this->createTenant('Access Waiter');
        $this->actingAsMember($tenant, 'waiter');

        $this->get($path)->assertStatus($status);
    }

    /**
     * @return array<string, array{0: string, 1: int}>
     */
    public static function kitchenPages(): array
    {
        return [
            'kitchen allowed' => ['/app/kitchen', 200],
            'orders allowed' => ['/app/orders', 200],
            'menu allowed' => ['/app/menu/products', 200],
            'pos denied' => ['/app/pos', 403],
            'tables denied' => ['/app/tables', 403],
            'members denied' => ['/app/members', 403],
        ];
    }

    #[DataProvider('kitchenPages')]
    public function test_kitchen_page_access(string $path, int $status): void
    {
        $tenant = $this->createTenant('Access Kitchen');
        $this->actingAsMember($tenant, 'kitchen');

        $this->get($path)->assertStatus($status);
    }

    public function test_member_can_view_members_but_not_manage_roles(): void
    {
        $tenant = $this->createTenant('Access Member');
        $this->actingAsMember($tenant, 'member');

        $this->get('/app/members')->assertOk();
        $this->get('/app/settings')->assertOk();
        $this->get('/app/roles')->assertForbidden();
    }

    public function test_owner_can_access_every_tenant_page(): void
    {
        $tenant = $this->createTenant('Access Owner');
        $this->actingAsMember($tenant, 'owner');

        foreach (['/app', '/app/zones', '/app/tables', '/app/orders', '/app/pos', '/app/cash',
            '/app/documents', '/app/kitchen', '/app/customers', '/app/menu/categories', '/app/menu/products',
            '/app/modifiers', '/app/members', '/app/roles', '/app/settings', '/app/settings/billing'] as $path) {
            $this->get($path)->assertOk();
        }
    }
}
