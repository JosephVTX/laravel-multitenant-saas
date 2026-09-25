<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\TenantStatus;
use App\Enums\UserStatus;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\Admin\StoreTenantRequest;
use App\Http\Requests\Api\V1\Admin\UpdateTenantRequest;
use App\Http\Resources\TenantResource;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class TenantController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $tenants = QueryBuilder::for(Tenant::query())
            ->allowedFilters(
                AllowedFilter::partial('name'),
                AllowedFilter::exact('slug'),
                AllowedFilter::exact('status'),
                AllowedFilter::exact('plan'),
                AllowedFilter::exact('locale'),
            )
            ->allowedSorts('name', 'slug', 'status', 'plan', 'created_at')
            ->allowedIncludes('users', 'projects')
            ->withCount(['users', 'projects'])
            ->defaultSort('-created_at')
            ->paginate($this->perPage($request))
            ->appends($request->query());

        return $this->paginated(TenantResource::collection($tenants), 'Tenants retrieved.');
    }

    public function store(StoreTenantRequest $request): JsonResponse
    {
        $data = $request->validated();
        $owner = $data['owner'] ?? null;
        unset($data['owner']);

        $tenant = DB::transaction(function () use ($data, $owner): Tenant {
            $tenant = Tenant::create($data);

            if ($owner) {
                $user = $tenant->users()->create([
                    'name' => $owner['name'],
                    'email' => $owner['email'],
                    'password' => $owner['password'],
                    'status' => UserStatus::Active,
                    'email_verified_at' => now(),
                ]);

                app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->getKey());
                $user->assignRole('tenant-admin');
            }

            return $tenant;
        });

        return api_success(new TenantResource($tenant->loadCount(['users', 'projects'])), 'Tenant created.', 201);
    }

    public function show(Tenant $tenant): JsonResponse
    {
        $tenant->loadCount(['users', 'projects']);

        return api_success(new TenantResource($tenant), 'Tenant retrieved.');
    }

    public function update(UpdateTenantRequest $request, Tenant $tenant): JsonResponse
    {
        $tenant->update($request->validated());

        return api_success(new TenantResource($tenant->loadCount(['users', 'projects'])), 'Tenant updated.');
    }

    public function destroy(Tenant $tenant): JsonResponse
    {
        $tenant->delete();

        return api_success(null, 'Tenant deleted.');
    }

    public function suspend(Tenant $tenant): JsonResponse
    {
        $tenant->update([
            'status' => TenantStatus::Suspended,
            'suspended_at' => now(),
        ]);

        return api_success(new TenantResource($tenant), 'Tenant suspended.');
    }

    public function activate(Tenant $tenant): JsonResponse
    {
        $tenant->update([
            'status' => TenantStatus::Active,
            'suspended_at' => null,
        ]);

        return api_success(new TenantResource($tenant), 'Tenant activated.');
    }
}
