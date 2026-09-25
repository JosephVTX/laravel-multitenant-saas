<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\TenantResource;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;

class ProfileController extends ApiController
{
    public function show(TenantContext $context): JsonResponse
    {
        $tenant = $context->require()->loadCount(['users', 'projects']);

        return api_success(new TenantResource($tenant), 'Tenant profile.');
    }
}
