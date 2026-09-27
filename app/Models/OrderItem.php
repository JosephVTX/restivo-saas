<?php

namespace App\Models;

use App\Enums\OrderItemStatus;
use App\Enums\Station;
use App\Enums\TaxType;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasUuid;
use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'order_id', 'product_id', 'product_name', 'tax_type', 'station',
    'unit_price', 'modifiers_total', 'quantity', 'line_total', 'tax_amount',
    'status', 'notes', 'sort_order', 'sent_at', 'ready_at',
])]
class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use BelongsToTenant, HasFactory, HasUuid;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tax_type' => TaxType::class,
            'station' => Station::class,
            'status' => OrderItemStatus::class,
            'unit_price' => 'decimal:2',
            'modifiers_total' => 'decimal:2',
            'quantity' => 'decimal:2',
            'line_total' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'sort_order' => 'integer',
            'sent_at' => 'datetime',
            'ready_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return HasMany<OrderItemModifier, $this>
     */
    public function modifiers(): HasMany
    {
        return $this->hasMany(OrderItemModifier::class);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', [
            OrderItemStatus::Void->value,
            OrderItemStatus::Delivered->value,
        ]);
    }
}
