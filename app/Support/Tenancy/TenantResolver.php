<?php

namespace App\Support\Tenancy;

use App\Models\Tenant;
use Closure;
use Illuminate\Contracts\Cache\Repository;

/**
 * Resolves tenants keeping the request critical path free of database hits.
 */
final class TenantResolver
{
    public function __construct(private readonly Repository $cache) {}

    public function findById(int|string $id): ?Tenant
    {
        return $this->remember("id:{$id}", function () use ($id): ?Tenant {
            return Tenant::query()->find($id);
        });
    }

    public function findByUuid(string $uuid): ?Tenant
    {
        return $this->remember("uuid:{$uuid}", function () use ($uuid): ?Tenant {
            return Tenant::query()->where('uuid', $uuid)->first();
        });
    }

    public function findBySlug(string $slug): ?Tenant
    {
        return $this->remember("slug:{$slug}", function () use ($slug): ?Tenant {
            return Tenant::query()->where('slug', $slug)->first();
        });
    }

    /**
     * Resolve a tenant from a numeric id, uuid or slug.
     */
    public function find(string $key): ?Tenant
    {
        if ($key === '') {
            return null;
        }

        if (ctype_digit($key)) {
            return $this->findById($key);
        }

        if (preg_match('/^[0-9a-fA-F-]{36}$/', $key) === 1) {
            return $this->findByUuid($key);
        }

        return $this->findBySlug($key);
    }

    public function forget(Tenant $tenant): void
    {
        $this->cache->forget("id:{$tenant->getKey()}");
        $this->cache->forget("uuid:{$tenant->uuid}");
        $this->cache->forget("slug:{$tenant->slug}");
    }

    /**
     * @param  Closure(): ?Tenant  $callback
     */
    private function remember(string $key, Closure $callback): ?Tenant
    {
        $ttl = (int) config('tenancy.cache.ttl', 3600);

        if ($ttl <= 0) {
            return $callback();
        }

        return $this->cache->remember(
            $this->cacheKey($key),
            now()->addSeconds($ttl),
            $callback,
        );
    }

    private function cacheKey(string $key): string
    {
        return config('tenancy.cache.prefix', 'tenant').':resolve:'.$key;
    }
}
