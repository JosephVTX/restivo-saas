<?php

namespace App\Services\Reports;

use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Enums\TableStatus;
use App\Models\DiningTable;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/**
 * Tenant-scoped KPI aggregation for the restaurant dashboard.
 *
 * Every query relies on the models' global tenant scope, so the report is
 * always limited to the active tenant. Hourly buckets are computed in PHP to
 * stay portable across MySQL (production) and SQLite (tests).
 */
class ReportService
{
    /**
     * @return array<string, mixed>
     */
    public function dashboard(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $sales = $this->sales($from, $to);

        return [
            'range' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'sales' => $sales,
            'sales_by_method' => $this->salesByMethod($from, $to),
            'sales_by_hour' => $this->salesByHour($from, $to),
            'top_products' => $this->topProducts($from, $to),
            'sales_by_category' => $this->salesByCategory($from, $to),
            'tables' => $this->tables(),
            'top_waiters' => $this->topWaiters($from, $to),
            'comparison' => $this->comparison($from, $to, $sales['total']),
            'open_orders' => $this->openOrders(),
        ];
    }

    /**
     * @return array{total: float, orders: int, average_ticket: float, tips: float}
     */
    protected function sales(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $orders = $this->paidOrders($from, $to)->get(['total', 'tip_total']);

        $total = round((float) $orders->sum('total'), 2);
        $count = $orders->count();

        return [
            'total' => $total,
            'orders' => $count,
            'average_ticket' => $count > 0 ? round($total / $count, 2) : 0.0,
            'tips' => round((float) $orders->sum('tip_total'), 2),
        ];
    }

    /**
     * @return array<int, array{method: string, label: string, total: float, count: int}>
     */
    protected function salesByMethod(CarbonImmutable $from, CarbonImmutable $to): array
    {
        return Payment::query()
            ->whereBetween('paid_at', [$from, $to])
            ->selectRaw('method, SUM(amount) as total, COUNT(*) as aggregate')
            ->groupBy('method')
            ->get()
            ->map(fn (Payment $payment): array => [
                'method' => $payment->method->value,
                'label' => $payment->method->label(),
                'total' => round((float) $payment->total, 2),
                'count' => (int) $payment->aggregate,
            ])
            ->all();
    }

    /**
     * @return array<int, array{hour: int, total: float, count: int}>
     */
    protected function salesByHour(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $orders = $this->paidOrders($from, $to)->get(['paid_at', 'total']);

        $buckets = $orders
            ->groupBy(fn (Order $order): int => (int) $order->paid_at->format('G'))
            ->map(fn ($group, int $hour): array => [
                'hour' => $hour,
                'total' => round((float) $group->sum('total'), 2),
                'count' => $group->count(),
            ]);

        return collect(range(0, 23))
            ->map(fn (int $hour): array => $buckets->get($hour, [
                'hour' => $hour,
                'total' => 0.0,
                'count' => 0,
            ]))
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{name: string, quantity: float, total: float}>
     */
    protected function topProducts(CarbonImmutable $from, CarbonImmutable $to): array
    {
        return $this->soldItems($from, $to)
            ->selectRaw('product_name, SUM(quantity) as quantity, SUM(line_total) as total')
            ->groupBy('product_name')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn (OrderItem $item): array => [
                'name' => $item->product_name,
                'quantity' => round((float) $item->quantity, 2),
                'total' => round((float) $item->total, 2),
            ])
            ->all();
    }

    /**
     * @return array<int, array{name: string, quantity: float, total: float}>
     */
    protected function salesByCategory(CarbonImmutable $from, CarbonImmutable $to): array
    {
        return $this->soldItems($from, $to)
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('menu_categories', 'menu_categories.id', '=', 'products.menu_category_id')
            ->selectRaw("COALESCE(menu_categories.name, 'Sin categoría') as name")
            ->selectRaw('SUM(order_items.quantity) as quantity, SUM(order_items.line_total) as total')
            ->groupBy('menu_categories.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn (OrderItem $item): array => [
                'name' => $item->name,
                'quantity' => round((float) $item->quantity, 2),
                'total' => round((float) $item->total, 2),
            ])
            ->all();
    }

    /**
     * @return array{total: int, occupied: int, available: int, occupancy_rate: float}
     */
    protected function tables(): array
    {
        $byStatus = DiningTable::query()
            ->get(['status'])
            ->groupBy(fn (DiningTable $table): string => $table->status->value)
            ->map(fn ($group): int => $group->count());

        $total = (int) $byStatus->sum();
        $occupied = (int) $byStatus->get(TableStatus::Occupied->value, 0)
            + (int) $byStatus->get(TableStatus::Billing->value, 0);

        return [
            'total' => $total,
            'occupied' => $occupied,
            'available' => (int) $byStatus->get(TableStatus::Available->value, 0),
            'occupancy_rate' => $total > 0 ? round($occupied / $total * 100, 1) : 0.0,
        ];
    }

    /**
     * @return array<int, array{name: string, orders: int, total: float}>
     */
    protected function topWaiters(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $rows = $this->paidOrders($from, $to)
            ->selectRaw('waiter_id, COUNT(*) as aggregate, SUM(total) as total')
            ->groupBy('waiter_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $names = User::query()
            ->whereIn('id', $rows->pluck('waiter_id')->filter()->all())
            ->pluck('name', 'id');

        return $rows
            ->map(fn (Order $order): array => [
                'name' => $order->waiter_id !== null
                    ? (string) ($names[$order->waiter_id] ?? 'Sin asignar')
                    : 'Sin asignar',
                'orders' => (int) $order->aggregate,
                'total' => round((float) $order->total, 2),
            ])
            ->all();
    }

    /**
     * @return array{previous_total: float, change_percentage: float|null}
     */
    protected function comparison(CarbonImmutable $from, CarbonImmutable $to, float $currentTotal): array
    {
        $seconds = $from->diffInSeconds($to) + 1;
        $previousTo = $from->subSecond();
        $previousFrom = $from->subSeconds($seconds);

        $previousTotal = round((float) $this->paidOrders($previousFrom, $previousTo)->sum('total'), 2);

        return [
            'previous_total' => $previousTotal,
            'change_percentage' => $previousTotal > 0
                ? round(($currentTotal - $previousTotal) / $previousTotal * 100, 1)
                : null,
        ];
    }

    protected function openOrders(): int
    {
        return Order::query()
            ->whereIn('status', [
                OrderStatus::Open->value,
                OrderStatus::Sent->value,
                OrderStatus::Served->value,
            ])
            ->count();
    }

    /**
     * @return Builder<Order>
     */
    protected function paidOrders(CarbonImmutable $from, CarbonImmutable $to)
    {
        return Order::query()
            ->where('status', OrderStatus::Paid->value)
            ->whereBetween('paid_at', [$from, $to]);
    }

    /**
     * @return Builder<OrderItem>
     */
    protected function soldItems(CarbonImmutable $from, CarbonImmutable $to)
    {
        return OrderItem::query()
            ->where('order_items.status', '!=', OrderItemStatus::Void->value)
            ->whereHas('order', function ($query) use ($from, $to): void {
                $query->where('status', OrderStatus::Paid->value)
                    ->whereBetween('paid_at', [$from, $to]);
            });
    }
}
