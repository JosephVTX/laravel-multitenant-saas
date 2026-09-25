<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\Admin\StoreUserRequest;
use App\Http\Requests\Api\V1\Admin\UpdateUserRequest;
use App\Http\Requests\Api\V1\AssignRolesRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\Tenancy\TenantResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class UserController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $users = QueryBuilder::for(User::query()->withoutTenantScope())
            ->allowedFilters(
                AllowedFilter::partial('name'),
                AllowedFilter::partial('email'),
                AllowedFilter::exact('status'),
                AllowedFilter::exact('is_super_admin'),
                AllowedFilter::exact('tenant_id'),
            )
            ->allowedSorts('name', 'email', 'status', 'created_at', 'last_login_at')
            ->allowedIncludes('tenant')
            ->defaultSort('name')
            ->paginate($this->perPage($request))
            ->appends($request->query());

        return $this->paginated(UserResource::collection($users), 'Users retrieved.');
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();
        $roles = $data['roles'] ?? [];
        $isSuperAdmin = $data['is_super_admin'] ?? false;
        $tenantKey = $data['tenant'] ?? null;
        unset($data['roles'], $data['is_super_admin'], $data['tenant']);

        $tenant = $tenantKey ? app(TenantResolver::class)->find($tenantKey) : null;

        $user = User::create($data);
        $user->forceFill([
            'tenant_id' => $tenant?->getKey(),
            'is_super_admin' => (bool) $isSuperAdmin && $tenant === null,
        ])->save();

        if ($roles && $tenant) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->getKey());
            $user->syncRoles($roles);
        }

        return api_success(new UserResource($user->load('tenant')), 'User created.', 201);
    }

    public function show(string $user): JsonResponse
    {
        $user = $this->findUser($user);
        app(PermissionRegistrar::class)->setPermissionsTeamId($user->tenant_id);
        $user->loadMissing(['tenant', 'roles.permissions']);

        return api_success(new UserResource($user), 'User retrieved.');
    }

    public function update(UpdateUserRequest $request, string $user): JsonResponse
    {
        $user = $this->findUser($user);
        $data = $request->validated();

        if (array_key_exists('is_super_admin', $data)) {
            $user->forceFill(['is_super_admin' => (bool) $data['is_super_admin'] && $user->tenant_id === null]);
            unset($data['is_super_admin']);
        }

        $user->update($data);

        return api_success(new UserResource($user->load('tenant')), 'User updated.');
    }

    public function destroy(string $user): JsonResponse
    {
        $this->findUser($user)->delete();

        return api_success(null, 'User deleted.');
    }

    public function assignRoles(AssignRolesRequest $request, string $user): JsonResponse
    {
        $user = $this->findUser($user);

        if ($user->tenant_id === null) {
            return api_error('Roles can only be assigned to tenant users.', 422, ['code' => 'roles_require_tenant']);
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId($user->tenant_id);
        $user->syncRoles($request->validated('roles'));

        return api_success(new UserResource($user->load('roles')), 'Roles updated.');
    }

    protected function findUser(string $uuid): User
    {
        return User::query()->withoutTenantScope()->where('uuid', $uuid)->firstOrFail();
    }
}
