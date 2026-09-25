<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\Tenancy\TenantResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\JWTGuard;

class AuthController extends ApiController
{
    public function login(LoginRequest $request): JsonResponse
    {
        /** @var JWTGuard $guard */
        $guard = auth('api');

        if (! $token = $guard->attempt($request->validated())) {
            return api_error('Invalid credentials.', 401, ['code' => 'invalid_credentials']);
        }

        /** @var User $user */
        $user = $guard->user();

        if (! $user->isActive()) {
            $guard->logout();

            return api_error('User account is not active.', 403, ['code' => 'user_inactive']);
        }

        $tenant = $user->tenant_id ? app(TenantResolver::class)->findById($user->tenant_id) : null;

        if ($user->tenant_id && (! $tenant || ! $tenant->isActive())) {
            $guard->logout();

            return api_error('Tenant account is not active.', 403, ['code' => 'tenant_inactive']);
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant?->getKey());

        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        return $this->tokenResponse($token, $user);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user('api')->loadMissing(['tenant', 'roles.permissions']);

        return api_success(new UserResource($user), 'Authenticated user.');
    }

    public function refresh(): JsonResponse
    {
        /** @var JWTGuard $guard */
        $guard = auth('api');

        try {
            $token = $guard->refresh();
        } catch (JWTException) {
            return api_error('Token cannot be refreshed.', 401, ['code' => 'token_not_refreshable']);
        }

        /** @var User $user */
        $user = $guard->user();

        return $this->tokenResponse($token, $user);
    }

    public function logout(): JsonResponse
    {
        /** @var JWTGuard $guard */
        $guard = auth('api');
        $guard->logout();

        return api_success(null, 'Logged out.');
    }

    protected function tokenResponse(string $token, User $user): JsonResponse
    {
        /** @var JWTGuard $guard */
        $guard = auth('api');

        $user->loadMissing(['tenant', 'roles.permissions']);

        return api_success([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $guard->factory()->getTTL() * 60,
            'user' => new UserResource($user),
        ], 'Authenticated.');
    }
}
