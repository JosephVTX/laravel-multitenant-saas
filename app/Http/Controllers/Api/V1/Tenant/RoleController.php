<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Api\ApiController;
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
        $this->authorize('roles.manage');

        $roles = QueryBuilder::for(Role::query()->whereNull('tenant_id'))
            ->allowedFilters(AllowedFilter::partial('name'))
            ->allowedSorts('name')
            ->allowedIncludes('permissions')
            ->defaultSort('name')
            ->paginate($this->perPage($request))
            ->appends($request->query());

        return $this->paginated(RoleResource::collection($roles), 'Assignable roles retrieved.');
    }
}
