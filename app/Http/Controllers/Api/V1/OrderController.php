<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\OrderStatus;
use App\Enums\TableStatus;
use App\Http\Requests\App\StoreOrderRequest;
use App\Http\Requests\App\UpdateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\DiningTable;
use App\Models\Order;
use App\Services\Orders\OrderService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;

class OrderController extends ApiController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return OrderResource::collection($this->indexQuery(
            Order::class,
            $request,
            filters: [
                AllowedFilter::exact('status'),
                AllowedFilter::exact('type'),
                AllowedFilter::callback('active', function (Builder $query, mixed $value): void {
                    if (filter_var($value, FILTER_VALIDATE_BOOLEAN)) {
                        $query->open();
                    }
                }),
                AllowedFilter::callback('table', function (Builder $query, mixed $value): void {
                    $query->whereHas('diningTable', fn (Builder $query) => $query->where('uuid', $value));
                }),
                $this->searchFilter(['number']),
            ],
            sorts: ['number', 'created_at', 'total'],
            withCount: ['items'],
            with: ['diningTable', 'waiter'],
        ));
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $order = app(OrderService::class)->open($request->validated(), $request->user());

        return (new OrderResource($order->load('diningTable', 'waiter')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(string $order): OrderResource
    {
        $model = $this->findByUuid(Order::class, $order);
        $this->authorize('view', $model);

        return new OrderResource($model->load('diningTable', 'waiter', 'items.modifiers', 'items.product'));
    }

    public function update(UpdateOrderRequest $request, string $order): OrderResource
    {
        $model = $this->findByUuid(Order::class, $order);
        $this->authorize('update', $model);

        $model->update($request->validated());

        return new OrderResource($model->load('diningTable', 'waiter', 'items.modifiers', 'items.product'));
    }

    public function send(string $order): OrderResource
    {
        $model = $this->findByUuid(Order::class, $order);
        $this->authorize('send', $model);

        $model = app(OrderService::class)->send($model);

        return new OrderResource($model->load('diningTable', 'waiter', 'items.modifiers', 'items.product'));
    }

    public function cancel(string $order): OrderResource
    {
        $model = $this->findByUuid(Order::class, $order);
        $this->authorize('cancel', $model);

        $model->update([
            'status' => OrderStatus::Cancelled,
            'closed_at' => now(),
        ]);

        if ($model->dining_table_id !== null) {
            DiningTable::query()
                ->whereKey($model->dining_table_id)
                ->update(['status' => TableStatus::Available->value]);
        }

        return new OrderResource($model->load('diningTable', 'waiter', 'items.modifiers', 'items.product'));
    }
}
