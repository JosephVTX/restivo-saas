<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\App\StoreDiningTableRequest;
use App\Http\Requests\App\UpdateDiningTableRequest;
use App\Http\Resources\DiningTableResource;
use App\Models\DiningTable;
use App\Models\Zone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;

class DiningTableController extends ApiController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return DiningTableResource::collection($this->indexQuery(
            DiningTable::class,
            $request,
            filters: [
                AllowedFilter::exact('status'),
                AllowedFilter::exact('is_active'),
                AllowedFilter::callback('zone', function (Builder $query, mixed $value): void {
                    $query->whereIn('zone_id', Zone::query()->where('uuid', $value)->pluck('id'));
                }),
                $this->searchFilter(['name']),
            ],
            sorts: ['name', 'capacity', 'sort_order', 'created_at'],
            with: ['zone'],
        ));
    }

    public function store(StoreDiningTableRequest $request): JsonResponse
    {
        $table = DiningTable::create($request->validated());
        $table->refresh();

        return (new DiningTableResource($table->load('zone')))->response()->setStatusCode(201);
    }

    public function show(string $table): DiningTableResource
    {
        return new DiningTableResource($this->findByUuid(DiningTable::class, $table)->load('zone'));
    }

    public function update(UpdateDiningTableRequest $request, string $table): DiningTableResource
    {
        $model = $this->findByUuid(DiningTable::class, $table);
        $model->update($request->validated());

        return new DiningTableResource($model->load('zone'));
    }

    public function destroy(string $table): JsonResponse
    {
        $this->findByUuid(DiningTable::class, $table)->delete();

        return response()->json(null, 204);
    }
}
