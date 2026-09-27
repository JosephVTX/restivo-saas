<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\App\StoreModifierGroupRequest;
use App\Http\Requests\App\UpdateModifierGroupRequest;
use App\Http\Resources\ModifierGroupResource;
use App\Models\Modifier;
use App\Models\ModifierGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Spatie\QueryBuilder\AllowedFilter;

class ModifierGroupController extends ApiController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return ModifierGroupResource::collection($this->indexQuery(
            ModifierGroup::class,
            $request,
            filters: [
                AllowedFilter::exact('selection_type'),
                AllowedFilter::exact('is_active'),
                $this->searchFilter(['name']),
            ],
            sorts: ['name', 'sort_order', 'created_at'],
            with: ['modifiers'],
        ));
    }

    public function store(StoreModifierGroupRequest $request): JsonResponse
    {
        $group = ModifierGroup::create(Arr::except($request->validated(), ['modifiers']));
        $group->refresh();

        $this->syncModifiers($group, $request->input('modifiers', []));

        return (new ModifierGroupResource($group->load('modifiers')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(string $modifier_group): ModifierGroupResource
    {
        return new ModifierGroupResource(
            $this->findByUuid(ModifierGroup::class, $modifier_group)->load('modifiers'),
        );
    }

    public function update(UpdateModifierGroupRequest $request, string $modifier_group): ModifierGroupResource
    {
        $model = $this->findByUuid(ModifierGroup::class, $modifier_group);
        $model->update(Arr::except($request->validated(), ['modifiers']));

        $this->syncModifiers($model, $request->input('modifiers', []));

        return new ModifierGroupResource($model->load('modifiers'));
    }

    public function destroy(string $modifier_group): JsonResponse
    {
        $this->findByUuid(ModifierGroup::class, $modifier_group)->delete();

        return response()->json(null, 204);
    }

    /**
     * Replace the group's options: update the ones carrying a known uuid,
     * create the rest, and soft-delete the options no longer present.
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    private function syncModifiers(ModifierGroup $group, array $items): void
    {
        $kept = [];

        foreach ($items as $index => $item) {
            $attributes = [
                'name' => $item['name'],
                'price' => $item['price'] ?? 0,
                'is_default' => $item['is_default'] ?? false,
                'sort_order' => $item['sort_order'] ?? $index,
                'is_active' => $item['is_active'] ?? true,
            ];

            $uuid = $item['uuid'] ?? null;

            $existing = $uuid !== null
                ? Modifier::query()->where('modifier_group_id', $group->id)->where('uuid', $uuid)->first()
                : null;

            if ($existing !== null) {
                $existing->update($attributes);
                $kept[] = $existing->uuid;

                continue;
            }

            $created = Modifier::create([...$attributes, 'modifier_group_id' => $group->id]);
            $kept[] = $created->uuid;
        }

        Modifier::query()
            ->where('modifier_group_id', $group->id)
            ->whereNotIn('uuid', $kept)
            ->delete();
    }
}
