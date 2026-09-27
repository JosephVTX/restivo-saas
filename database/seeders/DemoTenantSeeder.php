<?php

namespace Database\Seeders;

use App\Enums\ModifierSelectionType;
use App\Enums\ProjectStatus;
use App\Enums\Station;
use App\Enums\TaxType;
use App\Enums\TenantStatus;
use App\Models\DiningTable;
use App\Models\MenuCategory;
use App\Models\Modifier;
use App\Models\ModifierGroup;
use App\Models\Product;
use App\Models\Project;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoTenantSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::factory()->create([
            'name' => 'Restaurante Demo',
            'slug' => 'demo',
            'status' => TenantStatus::Active,
            'locale' => 'es',
            'settings' => [
                'timezone' => 'America/Lima',
                'currency' => 'PEN',
            ],
        ]);

        // Creating the tenant provisioned the roles for its team.
        $this->createMember($tenant, 'Demo Owner', 'owner@example.com', 'owner');

        $this->createMember($tenant, 'Demo Waiter', 'waiter@example.com', 'waiter', 'Mozo');
        $this->createMember($tenant, 'Demo Cashier', 'cashier@example.com', 'cashier', 'Cajero');
        $this->createMember($tenant, 'Demo Kitchen', 'kitchen@example.com', 'kitchen', 'Cocinero');

        $salon = Zone::factory()->create(['tenant_id' => $tenant->getKey(), 'name' => 'Salón', 'sort_order' => 1]);
        $terraza = Zone::factory()->create(['tenant_id' => $tenant->getKey(), 'name' => 'Terraza', 'sort_order' => 2]);

        DiningTable::factory()->count(6)->create([
            'tenant_id' => $tenant->getKey(),
            'zone_id' => $salon->getKey(),
        ]);

        DiningTable::factory()->count(4)->create([
            'tenant_id' => $tenant->getKey(),
            'zone_id' => $terraza->getKey(),
        ]);

        $entradas = MenuCategory::factory()->create([
            'tenant_id' => $tenant->getKey(),
            'name' => 'Entradas',
            'station' => Station::Kitchen,
            'sort_order' => 1,
        ]);
        $fondos = MenuCategory::factory()->create([
            'tenant_id' => $tenant->getKey(),
            'name' => 'Fondos',
            'station' => Station::Kitchen,
            'sort_order' => 2,
        ]);
        $bebidas = MenuCategory::factory()->create([
            'tenant_id' => $tenant->getKey(),
            'name' => 'Bebidas',
            'station' => Station::Bar,
            'sort_order' => 3,
        ]);
        $postres = MenuCategory::factory()->create([
            'tenant_id' => $tenant->getKey(),
            'name' => 'Postres',
            'station' => Station::Kitchen,
            'sort_order' => 4,
        ]);

        $menu = [
            ['Ceviche clásico', 38.0, $entradas, Station::Kitchen],
            ['Causa limeña', 25.0, $entradas, Station::Kitchen],
            ['Anticuchos', 28.0, $entradas, Station::Kitchen],
            ['Lomo saltado', 42.0, $fondos, Station::Kitchen],
            ['Ají de gallina', 34.0, $fondos, Station::Kitchen],
            ['Arroz con pollo', 30.0, $fondos, Station::Kitchen],
            ['Pollo a la brasa (1/4)', 32.0, $fondos, Station::Kitchen],
            ['Tallarín saltado', 36.0, $fondos, Station::Kitchen],
            ['Chicha morada (jarra)', 18.0, $bebidas, Station::Bar],
            ['Inca Kola (500 ml)', 8.0, $bebidas, Station::Bar],
            ['Limonada frozen', 15.0, $bebidas, Station::Bar],
            ['Café pasado', 8.0, $bebidas, Station::Bar],
            ['Suspiro a la limeña', 18.0, $postres, Station::Kitchen],
            ['Mazamorra morada', 14.0, $postres, Station::Kitchen],
        ];

        $fondosProducts = [];

        foreach ($menu as $index => [$name, $price, $category, $station]) {
            $product = Product::factory()->create([
                'tenant_id' => $tenant->getKey(),
                'menu_category_id' => $category->getKey(),
                'name' => $name,
                'description' => null,
                'price' => $price,
                'tax_type' => TaxType::Gravado,
                'station' => $station,
                'is_available' => true,
                'sort_order' => $index,
            ]);

            if ($category->is($fondos)) {
                $fondosProducts[] = $product;
            }
        }

        $termGroup = ModifierGroup::factory()->create([
            'tenant_id' => $tenant->getKey(),
            'name' => 'Término de la carne',
            'selection_type' => ModifierSelectionType::Single,
            'is_required' => true,
            'min_selections' => 1,
            'max_selections' => 1,
            'sort_order' => 1,
        ]);

        foreach (['Poco hecho', 'Al punto', 'Bien hecho'] as $order => $name) {
            Modifier::factory()->create([
                'tenant_id' => $tenant->getKey(),
                'modifier_group_id' => $termGroup->getKey(),
                'name' => $name,
                'price' => 0,
                'sort_order' => $order,
            ]);
        }

        $extrasGroup = ModifierGroup::factory()->create([
            'tenant_id' => $tenant->getKey(),
            'name' => 'Extras',
            'selection_type' => ModifierSelectionType::Multiple,
            'is_required' => false,
            'min_selections' => 0,
            'max_selections' => 5,
            'sort_order' => 2,
        ]);

        $extras = [
            ['Porción extra de papas', 5.0],
            ['Huevo', 3.0],
            ['Queso', 3.0],
        ];

        foreach ($extras as $order => [$name, $price]) {
            Modifier::factory()->create([
                'tenant_id' => $tenant->getKey(),
                'modifier_group_id' => $extrasGroup->getKey(),
                'name' => $name,
                'price' => $price,
                'sort_order' => $order,
            ]);
        }

        foreach ($fondosProducts as $product) {
            $product->modifierGroups()->attach([
                $termGroup->getKey(),
                $extrasGroup->getKey(),
            ]);
        }

        Project::factory()
            ->count(6)
            ->sequence(fn ($sequence) => [
                'status' => $sequence->index % 3 === 0 ? ProjectStatus::Active : ProjectStatus::Draft,
            ])
            ->create(['tenant_id' => $tenant->getKey()]);
    }

    private function createMember(
        Tenant $tenant,
        string $name,
        string $email,
        string $role,
        ?string $jobTitle = null,
    ): User {
        $user = User::factory()->create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make('password'),
        ]);

        $tenant->memberships()->create([
            'user_id' => $user->getKey(),
            'job_title' => $jobTitle,
            'joined_at' => now(),
        ]);

        $user->assignRole($role);

        return $user;
    }
}
