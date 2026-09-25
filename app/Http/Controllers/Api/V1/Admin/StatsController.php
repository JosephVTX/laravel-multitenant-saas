<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\Project;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;

class StatsController extends ApiController
{
    public function __invoke(): JsonResponse
    {
        return api_success([
            'tenants' => [
                'total' => Tenant::count(),
                'active' => Tenant::where('status', 'active')->count(),
                'suspended' => Tenant::where('status', 'suspended')->count(),
            ],
            'users' => [
                'total' => User::query()->withoutTenantScope()->count(),
                'super_admins' => User::query()->withoutTenantScope()->where('is_super_admin', true)->count(),
            ],
            'projects' => Project::query()->withoutTenantScope()->count(),
            'roles' => Role::count(),
        ], 'Platform statistics.');
    }
}
