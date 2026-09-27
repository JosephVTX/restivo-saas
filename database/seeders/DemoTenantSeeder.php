<?php

namespace Database\Seeders;

use App\Enums\ModifierSelectionType;
use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Enums\Station;
use App\Enums\TableStatus;
use App\Enums\TaxType;
use App\Enums\TenantStatus;
use App\Models\Customer;
use App\Models\DiningTable;
use App\Models\MenuCategory;
use App\Models\Modifier;
use App\Models\ModifierGroup;
use App\Models\Order;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Zone;
use App\Services\Billing\DocumentService;
use App\Services\Cash\CashSessionService;
use App\Services\Orders\OrderService;
use App\Services\Payments\PaymentService;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

class DemoTenantSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::factory()->create([
            'name' => 'Rincón Limeño',
            'slug' => 'rincon-limeno',
            'status' => TenantStatus::Active,
            'locale' => 'es',
            'settings' => [
                'timezone' => 'America/Lima',
                'currency' => 'PEN',
            ],
        ]);

        app(TenantContext::class)->set($tenant);

        $owner = $this->createMember($tenant, 'Rosa Quispe', 'owner@example.com', 'owner', 'Propietaria');
        $waiter = $this->createMember($tenant, 'Luis Ramírez', 'waiter@example.com', 'waiter', 'Mozo');
        $waiter2 = $this->createMember($tenant, 'Karla Torres', 'karla@example.com', 'waiter', 'Mozo');
        $cashier = $this->createMember($tenant, 'Jorge Salas', 'cashier@example.com', 'cashier', 'Cajero');
        $kitchen = $this->createMember($tenant, 'Miguel Ávila', 'kitchen@example.com', 'kitchen', 'Chef');

        $tables = $this->seedZonesAndTables($tenant);
        $products = $this->seedMenu($tenant);
        $this->seedModifiers($tenant, $products);
        $this->seedCustomers($tenant);

        $orders = app(OrderService::class);
        $payments = app(PaymentService::class);
        $cash = app(CashSessionService::class);
        $documents = app(DocumentService::class);

        $allProducts = $products->values();

        $this->seedHistoricalOrders($orders, $payments, $allProducts, $tables, $waiter, $waiter2, $cashier);

        $cash->open($cashier, ['opening_amount' => 200, 'notes' => 'Turno del día']);

        $todayOrders = $this->seedTodayOrders($orders, $payments, $allProducts, $tables, $waiter, $waiter2, $cashier);
        $this->seedKitchenOrders($orders, $allProducts, $tables, $waiter, $waiter2);

        foreach ($todayOrders->take(3) as $paidOrder) {
            $documents->emit($paidOrder, ['type' => 'nota_venta'], $paidOrder->payments->first());
        }
    }

    /**
     * @return Collection<int, DiningTable>
     */
    private function seedZonesAndTables(Tenant $tenant): Collection
    {
        $definitions = [
            ['Salón principal', 10, 4, 1],
            ['Terraza', 8, 4, 2],
            ['Barra', 5, 2, 3],
            ['Segundo piso', 6, 6, 4],
        ];

        $tables = collect();
        $number = 1;

        foreach ($definitions as [$name, $count, $capacity, $order]) {
            $zone = Zone::factory()->create([
                'tenant_id' => $tenant->getKey(),
                'name' => $name,
                'sort_order' => $order,
            ]);

            for ($i = 0; $i < $count; $i++) {
                $tables->push(DiningTable::factory()->create([
                    'tenant_id' => $tenant->getKey(),
                    'zone_id' => $zone->getKey(),
                    'name' => 'Mesa '.$number,
                    'capacity' => max(2, $capacity + random_int(-1, 1)),
                    'status' => TableStatus::Available,
                    'sort_order' => $number,
                ]));

                $number++;
            }
        }

        return $tables;
    }

    /**
     * @return Collection<int, Product>
     */
    private function seedMenu(Tenant $tenant): Collection
    {
        $categories = [
            'Entradas' => ['station' => Station::Kitchen, 'color' => '#16a34a', 'sort' => 1],
            'Fondos' => ['station' => Station::Kitchen, 'color' => '#dc2626', 'sort' => 2],
            'Parrillas' => ['station' => Station::Kitchen, 'color' => '#b91c1c', 'sort' => 3],
            'Bebidas' => ['station' => Station::Bar, 'color' => '#0ea5e9', 'sort' => 4],
            'Cervezas' => ['station' => Station::Bar, 'color' => '#f59e0b', 'sort' => 5],
            'Postres' => ['station' => Station::Kitchen, 'color' => '#f472b6', 'sort' => 6],
            'Jugos' => ['station' => Station::Bar, 'color' => '#22c55e', 'sort' => 7],
        ];

        $models = [];

        foreach ($categories as $name => $meta) {
            $models[$name] = MenuCategory::factory()->create([
                'tenant_id' => $tenant->getKey(),
                'name' => $name,
                'station' => $meta['station'],
                'color' => $meta['color'],
                'sort_order' => $meta['sort'],
            ]);
        }

        $menu = [
            // entradas
            ['1', 'Ceviche clásico', 38.0, 'Entradas', 'porción'],
            ['2', 'Ceviche mixto', 48.0, 'Entradas', 'porción'],
            ['3', 'Leche de tigre', 25.0, 'Entradas', 'vaso'],
            ['4', 'Tiradito de pescado', 42.0, 'Entradas', 'porción'],
            ['10', 'Causa limeña', 25.0, 'Entradas', 'porción'],
            ['11', 'Papa a la huancaína', 18.0, 'Entradas', 'porción'],
            ['12', 'Anticuchos', 28.0, 'Entradas', 'porción'],
            ['13', 'Tamal criollo', 15.0, 'Entradas', 'unidad'],
            ['14', 'Chicharrón de calamar', 32.0, 'Entradas', 'porción'],
            // fondos
            ['20', 'Lomo saltado', 42.0, 'Fondos', 'porción'],
            ['21', 'Ají de gallina', 35.0, 'Fondos', 'porción'],
            ['22', 'Arroz con pollo', 32.0, 'Fondos', 'porción'],
            ['23', 'Seco de res con frejoles', 38.0, 'Fondos', 'porción'],
            ['24', 'Carapulcra con sopa seca', 34.0, 'Fondos', 'porción'],
            ['25', 'Tallarín saltado', 36.0, 'Fondos', 'porción'],
            ['26', 'Pollo a la brasa (1/4)', 25.0, 'Fondos', 'porción'],
            ['27', 'Pollo a la brasa (1/2)', 45.0, 'Fondos', 'porción'],
            ['28', 'Arroz con mariscos', 40.0, 'Fondos', 'porción'],
            ['29', 'Chanfainita', 30.0, 'Fondos', 'porción'],
            // parrillas
            ['30', 'Anticucho de corazón', 30.0, 'Parrillas', 'porción'],
            ['31', 'Mollejitas al carbón', 32.0, 'Parrillas', 'porción'],
            ['32', 'Costillas de cerdo', 45.0, 'Parrillas', 'porción'],
            // bebidas
            ['50', 'Chicha morada (jarra 1L)', 18.0, 'Bebidas', 'jarra'],
            ['51', 'Chicha morada (vaso)', 7.0, 'Bebidas', 'vaso'],
            ['52', 'Limonada frozen', 15.0, 'Bebidas', 'vaso'],
            ['53', 'Inca Kola 500 ml', 8.0, 'Bebidas', 'unidad'],
            ['54', 'Coca Cola 500 ml', 8.0, 'Bebidas', 'unidad'],
            ['55', 'Agua San Luis 600 ml', 5.0, 'Bebidas', 'unidad'],
            // cervezas
            ['60', 'Cusqueña 650 ml', 14.0, 'Cervezas', 'unidad'],
            ['61', 'Pilsen Callao 650 ml', 13.0, 'Cervezas', 'unidad'],
            ['62', 'Corona 355 ml', 15.0, 'Cervezas', 'unidad'],
            // postres
            ['70', 'Suspiro a la limeña', 18.0, 'Postres', 'porción'],
            ['71', 'Picarones (3 unid.)', 15.0, 'Postres', 'porción'],
            ['72', 'Mazamorra morada', 12.0, 'Postres', 'porción'],
            ['73', 'Arroz con leche', 12.0, 'Postres', 'porción'],
            // jugos
            ['80', 'Jugo de papaya', 10.0, 'Jugos', 'vaso'],
            ['81', 'Jugo de naranja', 10.0, 'Jugos', 'vaso'],
        ];

        $products = collect();
        $sort = 0;

        foreach ($menu as [$code, $name, $price, $categoryName, $unit]) {
            $category = $models[$categoryName];

            $products->push(Product::factory()->create([
                'tenant_id' => $tenant->getKey(),
                'menu_category_id' => $category->getKey(),
                'name' => $name,
                'code' => $code,
                'price' => $price,
                'tax_type' => TaxType::Gravado,
                'station' => $categories[$categoryName]['station'],
                'unit' => $unit,
                'is_available' => true,
                'sort_order' => $sort++,
            ]));
        }

        return $products;
    }

    /**
     * @param  Collection<int, Product>  $products
     */
    private function seedModifiers(Tenant $tenant, Collection $products): void
    {
        $term = ModifierGroup::factory()->create([
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
                'modifier_group_id' => $term->getKey(),
                'name' => $name,
                'price' => 0,
                'sort_order' => $order,
            ]);
        }

        $picante = ModifierGroup::factory()->create([
            'tenant_id' => $tenant->getKey(),
            'name' => 'Nivel de picante',
            'selection_type' => ModifierSelectionType::Single,
            'is_required' => true,
            'min_selections' => 1,
            'max_selections' => 1,
            'sort_order' => 2,
        ]);

        foreach (['Sin picante', 'Ají suave', 'Ají regular', 'Bien picante'] as $order => $name) {
            Modifier::factory()->create([
                'tenant_id' => $tenant->getKey(),
                'modifier_group_id' => $picante->getKey(),
                'name' => $name,
                'price' => 0,
                'sort_order' => $order,
            ]);
        }

        $extras = ModifierGroup::factory()->create([
            'tenant_id' => $tenant->getKey(),
            'name' => 'Extras',
            'selection_type' => ModifierSelectionType::Multiple,
            'is_required' => false,
            'min_selections' => 0,
            'max_selections' => 5,
            'sort_order' => 3,
        ]);

        foreach ([['Porción extra de papas', 5.0], ['Huevo', 3.0], ['Queso', 3.0], ['Choclo extra', 2.0]] as $order => [$name, $price]) {
            Modifier::factory()->create([
                'tenant_id' => $tenant->getKey(),
                'modifier_group_id' => $extras->getKey(),
                'name' => $name,
                'price' => $price,
                'sort_order' => $order,
            ]);
        }

        $byCode = $products->keyBy('code');

        foreach (['1', '2', '3', '4'] as $code) {
            $byCode->get($code)?->modifierGroups()->attach($picante->getKey());
        }

        foreach (['30', '31', '32', '20'] as $code) {
            $byCode->get($code)?->modifierGroups()->attach($term->getKey());
        }

        foreach (['20', '21', '22', '23', '24', '25', '26', '27', '28', '29'] as $code) {
            $byCode->get($code)?->modifierGroups()->attach($extras->getKey());
        }
    }

    private function seedCustomers(Tenant $tenant): void
    {
        $customers = [
            ['Ana Gutiérrez', 'dni', '45876123'],
            ['Carlos Mendoza', 'dni', '40782234'],
            ['Lucía Ferrer', 'dni', '73214588'],
            ['Corporación Andina S.A.C.', 'ruc', '20512345678'],
            ['Textiles del Norte E.I.R.L.', 'ruc', '20605432109'],
        ];

        foreach ($customers as [$name, $type, $number]) {
            Customer::factory()->create([
                'tenant_id' => $tenant->getKey(),
                'name' => $name,
                'doc_type' => $type,
                'doc_number' => $number,
            ]);
        }
    }

    /**
     * @param  Collection<int, Product>  $products
     * @param  Collection<int, DiningTable>  $tables
     */
    private function seedHistoricalOrders(
        OrderService $orders,
        PaymentService $payments,
        Collection $products,
        Collection $tables,
        User $waiter,
        User $waiter2,
        User $cashier,
    ): void {
        $methods = [
            PaymentMethod::Cash->value,
            PaymentMethod::Yape->value,
            PaymentMethod::Plin->value,
            PaymentMethod::Card->value,
            PaymentMethod::Transfer->value,
        ];

        for ($daysAgo = 6; $daysAgo >= 1; $daysAgo--) {
            $count = random_int(4, 7);

            for ($i = 0; $i < $count; $i++) {
                $at = CarbonImmutable::now()
                    ->subDays($daysAgo)
                    ->setTime(random_int(12, 21), random_int(0, 59));

                $this->paidOrder(
                    $orders,
                    $payments,
                    $products,
                    $tables->random(),
                    $i % 2 === 0 ? $waiter : $waiter2,
                    $cashier,
                    $at,
                    $methods[array_rand($methods)],
                    random_int(0, 4) === 0 ? random_int(2, 10) : 0.0,
                );
            }
        }
    }

    /**
     * @param  Collection<int, Product>  $products
     * @param  Collection<int, DiningTable>  $tables
     * @return Collection<int, Order>
     */
    private function seedTodayOrders(
        OrderService $orders,
        PaymentService $payments,
        Collection $products,
        Collection $tables,
        User $waiter,
        User $waiter2,
        User $cashier,
    ): Collection {
        $paid = collect();

        $schedule = [
            [3, 25, PaymentMethod::Cash->value, 0.0],
            [2, 10, PaymentMethod::Yape->value, 5.0],
            [1, 40, PaymentMethod::Card->value, 8.0],
            [0, 55, PaymentMethod::Plin->value, 0.0],
            [0, 20, PaymentMethod::Cash->value, 3.0],
        ];

        foreach ($schedule as $index => [$hoursAgo, $minutesAgo, $method, $tip]) {
            $at = CarbonImmutable::now()->subHours($hoursAgo)->subMinutes($minutesAgo);

            $paid->push($this->paidOrder(
                $orders,
                $payments,
                $products,
                $tables->get($index),
                $index % 2 === 0 ? $waiter : $waiter2,
                $cashier,
                $at,
                $method,
                $tip,
            ));
        }

        return $paid;
    }

    /**
     * @param  Collection<int, Product>  $products
     * @param  Collection<int, DiningTable>  $tables
     */
    private function seedKitchenOrders(
        OrderService $orders,
        Collection $products,
        Collection $tables,
        User $waiter,
        User $waiter2,
    ): void {
        $openTables = $tables->slice(10, 4)->values();

        // Three orders already sent to the kitchen, in different stages.
        $orderA = $this->openOrder($orders, $products, $openTables[0], $waiter, true);
        $orderB = $this->openOrder($orders, $products, $openTables[1], $waiter2, true);
        $orderC = $this->openOrder($orders, $products, $openTables[2], $waiter, true);

        foreach ($orderB->items->take(1) as $item) {
            $orders->advanceItem($item, OrderItemStatus::Preparing);
        }

        foreach ($orderC->items as $item) {
            $orders->advanceItem($item, OrderItemStatus::Ready);
        }

        // One table waiting to be billed.
        $billingOrder = $this->openOrder($orders, $products, $openTables[3], $waiter2, true);
        $billingOrder->forceFill(['status' => OrderStatus::Served])->save();
        $openTables[3]->update(['status' => TableStatus::Billing]);

        // One order just opened, not sent yet.
        $this->openOrder($orders, $products, $tables->get(20), $waiter, false);
    }

    /**
     * @param  Collection<int, Product>  $products
     * @param  Collection<int, DiningTable>  $tables
     */
    private function paidOrder(
        OrderService $orders,
        PaymentService $payments,
        Collection $products,
        DiningTable $table,
        User $waiter,
        User $cashier,
        CarbonImmutable $at,
        string $method,
        float $tip,
    ): Order {
        $order = $orders->open([
            'type' => OrderType::DineIn->value,
            'dining_table_id' => $table->getKey(),
            'guests' => random_int(1, 6),
        ], $waiter);

        foreach ($this->randomItems($products) as [$product, $quantity]) {
            $orders->addItem($order, ['product_id' => $product->getKey(), 'quantity' => $quantity]);
        }

        $orders->send($order);
        $order->refresh();

        $total = (float) $order->total;
        $received = $method === PaymentMethod::Cash->value ? (float) (ceil(($total + $tip) / 10) * 10) : null;

        $payment = $payments->pay($order, [
            'method' => $method,
            'amount' => $total,
            'tip' => $tip,
            'received_amount' => $received,
        ], $cashier);

        $openedAt = $at->subMinutes(random_int(25, 75));

        $order->forceFill([
            'opened_at' => $openedAt,
            'paid_at' => $at,
            'closed_at' => $at,
            'created_at' => $openedAt,
            'updated_at' => $at,
        ])->save();

        $payment->forceFill([
            'paid_at' => $at,
            'created_at' => $at,
            'updated_at' => $at,
        ])->save();

        return $order->refresh();
    }

    /**
     * @param  Collection<int, Product>  $products
     */
    private function openOrder(
        OrderService $orders,
        Collection $products,
        DiningTable $table,
        User $waiter,
        bool $send,
    ): Order {
        $order = $orders->open([
            'type' => OrderType::DineIn->value,
            'dining_table_id' => $table->getKey(),
            'guests' => random_int(2, 5),
        ], $waiter);

        foreach ($this->randomItems($products) as [$product, $quantity]) {
            $orders->addItem($order, ['product_id' => $product->getKey(), 'quantity' => $quantity]);
        }

        if ($send) {
            $orders->send($order);
        }

        return $order->refresh();
    }

    /**
     * @param  Collection<int, Product>  $products
     * @return array<int, array{0: Product, 1: int}>
     */
    private function randomItems(Collection $products): array
    {
        return $products
            ->random(random_int(2, 4))
            ->map(fn (Product $product): array => [$product, random_int(1, 3)])
            ->all();
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
