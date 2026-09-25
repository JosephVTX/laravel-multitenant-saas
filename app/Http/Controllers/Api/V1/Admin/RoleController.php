<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\Admin\StoreRoleRequest;
use App\Http\Requests\Api\V1\Admin\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class RoleController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $roles = QueryBuilder::for(Role::query())
            ->allowedFilters(
                AllowedFilter::partial('name'),
                AllowedFilter::exact('guard_name'),
                AllowedFilter::exact('tenant_id'),
            )
            ->allowedSorts('name', 'created_at')
            ->allowedIncludes('permissions')
            ->defaultSort('name')
            ->paginate($this->perPage($request))
            ->appends($request->query());

        return $this->paginated(RoleResource::collection($roles), 'Roles retrieved.');
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = Role::create(['name' => $request->validated('name'), 'guard_name' => 'api']);
        $role->syncPermissions($request->validated('permissions', []));

        return api_success(new RoleResource($role->load('permissions')), 'Role created.', 201);
    }

    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        if ($request->has('permissions')) {
            $role->syncPermissions($request->validated('permissions', []));
        }

        return api_success(new RoleResource($role->load('permissions')), 'Role updated.');
    }

    public function destroy(Role $role): JsonResponse
    {
        $role->delete();

        return api_success(null, 'Role deleted.');
    }
}
