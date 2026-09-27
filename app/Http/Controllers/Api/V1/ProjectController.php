<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\App\StoreProjectRequest;
use App\Http\Requests\App\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;

class ProjectController extends ApiController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return ProjectResource::collection($this->indexQuery(
            Project::class,
            $request,
            filters: [
                AllowedFilter::exact('status'),
                $this->searchFilter(['name', 'description']),
            ],
            sorts: ['name', 'status', 'created_at', 'due_date'],
        ));
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $project = Project::create($request->validated());

        return (new ProjectResource($project))->response()->setStatusCode(201);
    }

    public function show(string $project): ProjectResource
    {
        return new ProjectResource($this->findByUuid(Project::class, $project));
    }

    public function update(UpdateProjectRequest $request, string $project): ProjectResource
    {
        $model = $this->findByUuid(Project::class, $project);
        $model->update($request->validated());

        return new ProjectResource($model);
    }

    public function destroy(string $project): JsonResponse
    {
        $this->findByUuid(Project::class, $project)->delete();

        return response()->json(null, 204);
    }
}
