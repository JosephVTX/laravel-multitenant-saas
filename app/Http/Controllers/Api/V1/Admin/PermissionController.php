<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\Admin\StorePermissionRequest;
use App\Http\Resources\PermissionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class PermissionController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $permissions = QueryBuilder::for(Permission::query())
            ->allowedFilters(
                AllowedFilter::partial('name'),
                AllowedFilter::exact('guard_name'),
            )
            ->allowedSorts('name', 'created_at')
            ->defaultSort('name')
            ->paginate($this->perPage($request))
            ->appends($request->query());

        return $this->paginated(PermissionResource::collection($permissions), 'Permissions retrieved.');
    }

    public function store(StorePermissionRequest $request): JsonResponse
    {
        $permission = Permission::create(['name' => $request->validated('name'), 'guard_name' => 'api']);

        return api_success(new PermissionResource($permission), 'Permission created.', 201);
    }
}
