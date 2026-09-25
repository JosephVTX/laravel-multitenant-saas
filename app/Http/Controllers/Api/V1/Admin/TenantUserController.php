<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\Admin\StoreUserRequest;
use App\Http\Resources\UserResource;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class TenantUserController extends ApiController
{
    public function index(Request $request, Tenant $tenant): JsonResponse
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->getKey());

        $users = QueryBuilder::for(User::query()->where('tenant_id', $tenant->getKey()))
            ->allowedFilters(
                AllowedFilter::partial('name'),
                AllowedFilter::partial('email'),
                AllowedFilter::exact('status'),
            )
            ->allowedSorts('name', 'email', 'status', 'created_at', 'last_login_at')
            ->allowedIncludes('roles')
            ->defaultSort('name')
            ->paginate($this->perPage($request))
            ->appends($request->query());

        return $this->paginated(UserResource::collection($users), 'Tenant users retrieved.');
    }

    public function store(StoreUserRequest $request, Tenant $tenant): JsonResponse
    {
        $data = $request->validated();
        $roles = $data['roles'] ?? [];
        unset($data['roles'], $data['is_super_admin'], $data['tenant']);

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->getKey());

        $user = $tenant->users()->create($data);

        if ($roles) {
            $user->syncRoles($roles);
        }

        return api_success(new UserResource($user->load('roles')), 'Tenant user created.', 201);
    }
}
