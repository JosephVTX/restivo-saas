<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\OrderItemStatus;
use App\Http\Requests\App\StoreOrderItemRequest;
use App\Http\Requests\App\UpdateOrderItemRequest;
use App\Http\Requests\App\UpdateOrderItemStatusRequest;
use App\Http\Resources\OrderItemResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Orders\OrderService;
use Illuminate\Http\JsonResponse;

class OrderItemController extends ApiController
{
    public function store(StoreOrderItemRequest $request, string $order): JsonResponse
    {
        $model = $this->findByUuid(Order::class, $order);
        $this->authorize('update', $model);

        $item = app(OrderService::class)->addItem($model, $request->validated());

        return (new OrderItemResource($item->load('modifiers', 'product')))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateOrderItemRequest $request, string $item): OrderItemResource
    {
        $model = $this->findByUuid(OrderItem::class, $item);
        $this->authorize('update', $model);

        $updated = app(OrderService::class)->updateItem($model, $request->validated());

        return new OrderItemResource($updated->load('modifiers', 'product'));
    }

    public function status(UpdateOrderItemStatusRequest $request, string $item): OrderItemResource
    {
        $model = $this->findByUuid(OrderItem::class, $item);
        $this->authorize('advance', $model);

        $updated = app(OrderService::class)->advanceItem(
            $model,
            OrderItemStatus::from($request->validated('status')),
        );

        return new OrderItemResource($updated->load('modifiers', 'product'));
    }

    public function destroy(string $item): JsonResponse
    {
        $model = $this->findByUuid(OrderItem::class, $item);
        $this->authorize('update', $model);

        app(OrderService::class)->voidItem($model);

        return response()->json(null, 204);
    }
}
