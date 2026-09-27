<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\App\CloseCashSessionRequest;
use App\Http\Requests\App\OpenCashSessionRequest;
use App\Http\Requests\App\StoreCashMovementRequest;
use App\Http\Resources\CashMovementResource;
use App\Http\Resources\CashSessionResource;
use App\Models\CashSession;
use App\Services\Cash\CashSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use RuntimeException;
use Spatie\QueryBuilder\AllowedFilter;

class CashSessionController extends ApiController
{
    public function __construct(private readonly CashSessionService $cash) {}

    public function current(Request $request): JsonResponse
    {
        $this->authorize('viewAny', CashSession::class);

        $session = $this->cash->activeSession();

        if ($session === null) {
            return response()->json(['data' => null, 'summary' => null]);
        }

        return (new CashSessionResource($session->load('user', 'closedBy', 'movements.user')->loadCount(['payments', 'movements'])))
            ->additional(['summary' => $this->cash->summary($session)])
            ->response();
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', CashSession::class);

        return CashSessionResource::collection($this->indexQuery(
            CashSession::class,
            $request,
            filters: [
                AllowedFilter::exact('status'),
            ],
            sorts: ['created_at', 'opened_at'],
            withCount: ['payments', 'movements'],
        ));
    }

    public function show(string $session): CashSessionResource
    {
        $model = $this->findByUuid(CashSession::class, $session);
        $this->authorize('view', $model);

        return (new CashSessionResource($model->load('user', 'closedBy', 'movements.user')->loadCount(['payments', 'movements'])))
            ->additional(['summary' => $this->cash->summary($model)]);
    }

    public function store(OpenCashSessionRequest $request): JsonResponse
    {
        try {
            $session = $this->cash->open($request->user(), $request->validated());
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return (new CashSessionResource($session->load('user', 'closedBy', 'movements.user')->loadCount(['payments', 'movements'])))
            ->response()
            ->setStatusCode(201);
    }

    public function close(CloseCashSessionRequest $request, string $session): JsonResponse
    {
        $model = $this->findByUuid(CashSession::class, $session);
        $this->authorize('close', $model);

        try {
            $model = $this->cash->close($model, $request->validated(), $request->user());
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return (new CashSessionResource($model->load('user', 'closedBy', 'movements.user')->loadCount(['payments', 'movements'])))
            ->additional(['summary' => $this->cash->summary($model)])
            ->response();
    }

    public function storeMovement(StoreCashMovementRequest $request, string $session): JsonResponse
    {
        $model = $this->findByUuid(CashSession::class, $session);
        $this->authorize('movement', $model);

        try {
            $movement = $this->cash->registerMovement($model, $request->validated(), $request->user());
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return (new CashMovementResource($movement->load('user')))
            ->response()
            ->setStatusCode(201);
    }
}
