<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\App\StorePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Payments\PaymentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use RuntimeException;
use Spatie\QueryBuilder\AllowedFilter;

class PaymentController extends ApiController
{
    public function __construct(private readonly PaymentService $payments) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Payment::class);

        return PaymentResource::collection($this->indexQuery(
            Payment::class,
            $request,
            filters: [
                AllowedFilter::exact('method'),
                AllowedFilter::callback('order', function (Builder $query, mixed $value): void {
                    $query->whereHas('order', fn (Builder $query) => $query->where('uuid', $value));
                }),
            ],
            sorts: ['paid_at', 'amount', 'created_at'],
            with: ['order', 'user'],
        ));
    }

    public function ordersIndex(string $order): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Payment::class);

        $model = $this->findByUuid(Order::class, $order);

        return PaymentResource::collection(
            $model->payments()->with('user')->orderBy('paid_at')->get(),
        );
    }

    public function store(StorePaymentRequest $request, string $order): JsonResponse
    {
        $model = $this->findByUuid(Order::class, $order);
        $this->authorize('create', Payment::class);

        try {
            $payment = $this->payments->pay($model, $request->validated(), $request->user());
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return (new PaymentResource($payment->load('order', 'user')))
            ->response()
            ->setStatusCode(201);
    }
}
