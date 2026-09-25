<?php

namespace App\Http\Middleware;

use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guarantees that a tenant context exists and is active before a tenant route
 * executes, so tenant scoped queries can never silently run unscoped.
 */
final class EnsureTenant
{
    public function __construct(private readonly TenantContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->context->tenant();

        if ($tenant === null) {
            abort(403, 'No active tenant context for this request.');
        }

        if (! $tenant->isActive()) {
            abort(403, 'The tenant account is not active.');
        }

        return $next($request);
    }
}
