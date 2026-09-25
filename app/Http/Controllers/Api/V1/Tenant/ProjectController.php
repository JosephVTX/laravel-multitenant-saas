<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\Tenant\StoreProjectRequest;
use App\Http\Requests\Api\V1\Tenant\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ProjectController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Project::class);

        $projects = QueryBuilder::for(Project::query())
            ->allowedFilters(
                AllowedFilter::partial('name'),
                AllowedFilter::exact('slug'),
                AllowedFilter::exact('status'),
                AllowedFilter::scope('published'),
            )
            ->allowedSorts('name', 'status', 'published_at', 'created_at')
            ->defaultSort('-created_at')
            ->paginate($this->perPage($request))
            ->appends($request->query());

        return $this->paginated(ProjectResource::collection($projects), 'Projects retrieved.');
    }

    public function store(StoreProjectRequest $request, TenantContext $context): JsonResponse
    {
        $this->authorize('create', Project::class);

        $project = $context->require()->projects()->create($request->validated());

        return api_success(new ProjectResource($project), 'Project created.', 201);
    }

    public function show(string $project): JsonResponse
    {
        $project = $this->findProject($project);
        $this->authorize('view', $project);

        return api_success(new ProjectResource($project), 'Project retrieved.');
    }

    public function update(UpdateProjectRequest $request, string $project): JsonResponse
    {
        $project = $this->findProject($project);
        $this->authorize('update', $project);

        $project->update($request->validated());

        return api_success(new ProjectResource($project), 'Project updated.');
    }

    public function destroy(string $project): JsonResponse
    {
        $project = $this->findProject($project);
        $this->authorize('delete', $project);

        $project->delete();

        return api_success(null, 'Project deleted.');
    }

    protected function findProject(string $uuid): Project
    {
        return Project::query()->where('uuid', $uuid)->firstOrFail();
    }
}
