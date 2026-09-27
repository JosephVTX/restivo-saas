<?php

namespace App\Services\Orders;

use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Enums\TableStatus;
use App\Models\DiningTable;
use App\Models\Modifier;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Single source of truth for order lifecycle and money math.
 *
 * Every write path (waiter taking an order, kitchen advancing a line, cashier
 * paying) goes through here so totals, statuses and table state stay coherent.
 */
class OrderService
{
    public function __construct(private readonly TaxCalculator $tax) {}

    /**
     * Open a new order, optionally occupying a table.
     *
     * @param  array{type: string, dining_table_id?: int|null, guests?: int, notes?: string|null}  $attributes
     */
    public function open(array $attributes, ?User $waiter = null): Order
    {
        return DB::transaction(function () use ($attributes, $waiter): Order {
            $table = isset($attributes['dining_table_id'])
                ? DiningTable::query()->find($attributes['dining_table_id'])
                : null;

            $order = Order::query()->create([
                'number' => $this->nextNumber(),
                'type' => $attributes['type'],
                'status' => OrderStatus::Open,
                'dining_table_id' => $table?->id,
                'waiter_id' => $waiter?->id,
                'guests' => $attributes['guests'] ?? 1,
                'notes' => $attributes['notes'] ?? null,
                'opened_at' => now(),
            ]);

            if ($table !== null) {
                $table->update(['status' => TableStatus::Occupied]);
            }

            return $order->refresh();
        });
    }

    /**
     * Add a product (with optional modifier ids) to an order.
     *
     * @param  array{product_id: int, quantity?: float|int, modifiers?: array<int, int>, notes?: string|null}  $attributes
     */
    public function addItem(Order $order, array $attributes): OrderItem
    {
        return DB::transaction(function () use ($order, $attributes): OrderItem {
            $product = Product::query()->findOrFail($attributes['product_id']);

            $item = $order->items()->create([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'tax_type' => $product->tax_type,
                'station' => $product->station,
                'unit_price' => $product->price,
                'quantity' => $attributes['quantity'] ?? 1,
                'modifiers_total' => 0,
                'notes' => $attributes['notes'] ?? null,
                'sort_order' => (int) $order->items()->max('sort_order') + 1,
            ]);

            $modifiersTotal = $this->syncModifiers($item, $attributes['modifiers'] ?? []);
            $this->priceItem($item, $modifiersTotal);

            $this->recalculate($order);

            return $item->refresh();
        });
    }

    /**
     * Update mutable fields of an order line.
     *
     * @param  array{quantity?: float|int, notes?: string|null, status?: string}  $attributes
     */
    public function updateItem(OrderItem $item, array $attributes): OrderItem
    {
        return DB::transaction(function () use ($item, $attributes): OrderItem {
            if (array_key_exists('quantity', $attributes)) {
                $item->quantity = $attributes['quantity'];
            }

            if (array_key_exists('notes', $attributes)) {
                $item->notes = $attributes['notes'];
            }

            if (array_key_exists('status', $attributes)) {
                $this->applyItemStatus($item, OrderItemStatus::from($attributes['status']));
            }

            $this->priceItem($item, (float) $item->modifiers_total);

            $this->recalculate($item->order);

            return $item->refresh();
        });
    }

    /**
     * Void an order line (kept for auditing, excluded from totals).
     */
    public function voidItem(OrderItem $item): void
    {
        DB::transaction(function () use ($item): void {
            $item->update(['status' => OrderItemStatus::Void]);
            $this->recalculate($item->order);
        });
    }

    /**
     * Advance a single kitchen line (pending -> preparing -> ready -> delivered).
     */
    public function advanceItem(OrderItem $item, OrderItemStatus $status): OrderItem
    {
        return DB::transaction(function () use ($item, $status): OrderItem {
            $this->applyItemStatus($item, $status);
            $item->save();

            return $item->refresh();
        });
    }

    /**
     * Send an order's pending lines to the kitchen.
     */
    public function send(Order $order): Order
    {
        return DB::transaction(function () use ($order): Order {
            $order->items()->whereNull('sent_at')->update(['sent_at' => now()]);

            if ($order->status === OrderStatus::Open) {
                $order->status = OrderStatus::Sent;
            }

            $order->save();

            return $order->refresh();
        });
    }

    /**
     * Close an order once paid: mark lines delivered and free the table.
     */
    public function close(Order $order): Order
    {
        return DB::transaction(function () use ($order): Order {
            $order->status = OrderStatus::Paid;
            $order->paid_at = $order->paid_at ?? now();
            $order->closed_at = now();
            $order->save();

            $order->items()
                ->whereNotIn('status', [OrderItemStatus::Void->value])
                ->update(['status' => OrderItemStatus::Delivered->value]);

            if ($order->dining_table_id !== null) {
                DiningTable::query()
                    ->whereKey($order->dining_table_id)
                    ->update(['status' => TableStatus::Available->value]);
            }

            return $order->refresh();
        });
    }

    /**
     * Recompute order totals from its non-void lines.
     */
    public function recalculate(Order $order): Order
    {
        /** @var Collection<int, OrderItem> $items */
        $items = $order->items()
            ->where('status', '!=', OrderItemStatus::Void->value)
            ->get();

        $total = round((float) $items->sum('line_total'), 2);
        $tax = round((float) $items->sum('tax_amount'), 2);

        $order->total = $total;
        $order->tax_total = $tax;
        $order->subtotal = round($total - $tax, 2);
        $order->save();

        return $order;
    }

    /**
     * @param  array<int, int>  $modifierIds
     * @return float total price of the selected modifiers (per unit)
     */
    private function syncModifiers(OrderItem $item, array $modifierIds): float
    {
        $modifierIds = array_values(array_unique(array_map('intval', $modifierIds)));

        if ($modifierIds === []) {
            return 0.0;
        }

        /** @var Collection<int, Modifier> $modifiers */
        $modifiers = Modifier::query()->whereIn('id', $modifierIds)->get();

        $total = 0.0;

        foreach ($modifiers as $modifier) {
            $item->modifiers()->create([
                'modifier_id' => $modifier->id,
                'modifier_name' => $modifier->name,
                'price' => $modifier->price,
                'quantity' => 1,
            ]);

            $total += (float) $modifier->price;
        }

        return round($total, 2);
    }

    private function priceItem(OrderItem $item, float $modifiersTotal): void
    {
        $gross = ((float) $item->unit_price + $modifiersTotal) * (float) $item->quantity;

        $item->modifiers_total = $modifiersTotal;
        $item->line_total = round($gross, 2);
        $item->tax_amount = $this->tax->igvFromGross($gross, $item->tax_type);
        $item->save();
    }

    private function applyItemStatus(OrderItem $item, OrderItemStatus $status): void
    {
        $item->status = $status;

        if ($status === OrderItemStatus::Preparing && $item->sent_at === null) {
            $item->sent_at = now();
        }

        if ($status === OrderItemStatus::Ready) {
            $item->ready_at = $item->ready_at ?? now();
        }
    }

    private function nextNumber(): int
    {
        return (int) Order::query()->max('number') + 1;
    }
}
