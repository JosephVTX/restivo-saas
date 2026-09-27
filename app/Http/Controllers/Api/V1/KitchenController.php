<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Enums\Station;
use App\Http\Resources\KitchenItemResource;
use App\Models\OrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KitchenController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('kitchen.view');

        $request->validate([
            'filter.station' => ['nullable', 'in:kitchen,bar'],
        ]);

        $items = OrderItem::query()
            ->whereHas('order', fn ($query) => $query->whereIn('status', [
                OrderStatus::Open->value,
                OrderStatus::Sent->value,
                OrderStatus::Served->value,
            ]))
            ->whereIn('status', [
                OrderItemStatus::Pending->value,
                OrderItemStatus::Preparing->value,
                OrderItemStatus::Ready->value,
            ])
            ->whereIn('station', [Station::Kitchen->value, Station::Bar->value])
            ->when($request->filled('filter.station'), fn ($query) => $query->where('station', $request->input('filter.station')))
            ->with(['order.diningTable', 'order.waiter', 'modifiers'])
            ->orderBy('sent_at')
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => KitchenItemResource::collection($items)->resolve(),
        ]);
    }
}
