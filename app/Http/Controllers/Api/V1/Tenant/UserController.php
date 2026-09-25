<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\AssignRolesRequest;
use App\Http\Requests\Api\V1\Tenant\StoreUserRequest;
use App\Http\Requests\Api\V1\Tenant\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class UserController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('users.view');

        $users = QueryBuilder::for(User::query())
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

        return $this->paginated(UserResource::collection($users), 'Users retrieved.');
    }

    public function store(StoreUserRequest $request, TenantContext $context): JsonResponse
    {
        $this->authorize('users.create');

        $data = $request->validated();
        $roles = $data['roles'] ?? [];
        unset($data['roles']);

        $user = DB::transaction(function () use ($data, $roles, $context): User {
            $user = $context->require()->users()->create($data);

            if ($roles) {
                $user->syncRoles($roles);
            }

            return $user;
        });

        return api_success(new UserResource($user->load('roles')), 'User created.', 201);
    }

    public function update(UpdateUserRequest $request, string $user): JsonResponse
    {
        $this->authorize('users.update');

        $user = $this->findUser($user);
        $user->update($request->validated());

        return api_success(new UserResource($user), 'User updated.');
    }

    public function destroy(string $user): JsonResponse
    {
        $this->authorize('users.delete');

        $user = $this->findUser($user);

        if ($user->is(request()->user())) {
            return api_error('You cannot delete your own account.', 422, ['code' => 'cannot_delete_self']);
        }

        $user->delete();

        return api_success(null, 'User deleted.');
    }

    public function assignRoles(AssignRolesRequest $request, string $user): JsonResponse
    {
        $this->authorize('users.update');

        $user = $this->findUser($user);
        app(PermissionRegistrar::class)->setPermissionsTeamId($user->tenant_id);
        $user->syncRoles($request->validated('roles'));

        return api_success(new UserResource($user->load('roles')), 'Roles updated.');
    }

    protected function findUser(string $uuid): User
    {
        return User::query()->where('uuid', $uuid)->firstOrFail();
    }
}
