<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\App\StoreZoneRequest;
use App\Http\Requests\App\UpdateZoneRequest;
use App\Http\Resources\ZoneResource;
use App\Models\Zone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;

class ZoneController extends ApiController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return ZoneResource::collection($this->indexQuery(
            Zone::class,
            $request,
            filters: [
                AllowedFilter::exact('is_active'),
                $this->searchFilter(['name']),
            ],
            sorts: ['name', 'sort_order', 'created_at'],
        ));
    }

    public function store(StoreZoneRequest $request): JsonResponse
    {
        $zone = Zone::create($request->validated());
        $zone->refresh();

        return (new ZoneResource($zone))->response()->setStatusCode(201);
    }

    public function show(string $zone): ZoneResource
    {
        return new ZoneResource($this->findByUuid(Zone::class, $zone));
    }

    public function update(UpdateZoneRequest $request, string $zone): ZoneResource
    {
        $model = $this->findByUuid(Zone::class, $zone);
        $model->update($request->validated());

        return new ZoneResource($model);
    }

    public function destroy(string $zone): JsonResponse
    {
        $this->findByUuid(Zone::class, $zone)->delete();

        return response()->json(null, 204);
    }
}
