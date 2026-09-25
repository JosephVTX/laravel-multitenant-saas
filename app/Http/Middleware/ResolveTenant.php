<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\Tenancy\TenantContext;
use App\Support\Tenancy\TenantResolver;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the tenant for the current request and binds it to the container.
 *
 * Must run after the "auth:api" middleware. Regular users are always scoped to
 * their own tenant; super administrators may target a tenant with the
 * configured header (X-Tenant), which enables the admin panel to operate on
 * any tenant without wildcard subdomains.
 */
final class ResolveTenant
{
    public function __construct(
        private readonly TenantContext $context,
        private readonly TenantResolver $resolver,
        private readonly PermissionRegistrar $permissions,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->resolve($request);

        $this->context->set($tenant);
        $this->permissions->setPermissionsTeamId($tenant?->getKey());

        if ($tenant !== null) {
            Log::withContext(['tenant_id' => $tenant->getKey()]);
        }

        return $next($request);
    }

    private function resolve(Request $request): ?Tenant
    {
        $user = $request->user();

        if ($user === null) {
            return null;
        }

        $header = config('tenancy.header', 'X-Tenant');

        if ($user->isSuperAdmin() && $header !== null && $request->filled($header)) {
            return $this->resolver->find((string) $request->header($header));
        }

        if ($user->tenant_id === null) {
            return null;
        }

        return $this->resolver->findById($user->tenant_id);
    }
}
