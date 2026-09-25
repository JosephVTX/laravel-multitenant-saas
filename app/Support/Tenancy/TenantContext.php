<?php

namespace App\Support\Tenancy;

use App\Models\Tenant;
use RuntimeException;

/**
 * Holds the tenant resolved for the current request.
 *
 * Registered as a scoped binding so it is reset between requests under
 * Laravel Octane (see TenancyServiceProvider).
 */
final class TenantContext
{
    private ?Tenant $tenant = null;

    private bool $resolved = false;

    public function set(?Tenant $tenant): void
    {
        $this->tenant = $tenant;
        $this->resolved = true;
    }

    public function clear(): void
    {
        $this->tenant = null;
        $this->resolved = false;
    }

    public function isResolved(): bool
    {
        return $this->resolved;
    }

    public function has(): bool
    {
        return $this->tenant !== null;
    }

    public function tenant(): ?Tenant
    {
        return $this->tenant;
    }

    public function id(): int|string|null
    {
        return $this->tenant?->getKey();
    }

    public function require(): Tenant
    {
        return $this->tenant ?? throw new RuntimeException('No tenant context has been resolved.');
    }

    /**
     * Build a cache key namespaced to the current tenant (or to the central
     * workspace when no tenant is active).
     */
    public function cacheKey(string $key): string
    {
        $prefix = config('tenancy.cache.prefix', 'tenant');

        return sprintf('%s:%s:%s', $prefix, $this->id() ?? 'central', $key);
    }
}
