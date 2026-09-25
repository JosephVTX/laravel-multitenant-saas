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
    /**
     * Bump when the cached payload shape changes so stale entries are ignored.
     */
    private const CACHE_VERSION = 2;

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
        $this->cache->forget($this->cacheKey("id:{$tenant->getKey()}"));
        $this->cache->forget($this->cacheKey("uuid:{$tenant->uuid}"));
        $this->cache->forget($this->cacheKey("slug:{$tenant->slug}"));
    }

    /**
     * Cache the tenant's raw attributes (never the serialized model) and
     * rehydrate a detached model from cache, so a resolution never costs a
     * database query while staying safe across Octane workers.
     *
     * @param  Closure(): ?Tenant  $callback
     */
    private function remember(string $key, Closure $callback): ?Tenant
    {
        $ttl = (int) config('tenancy.cache.ttl', 3600);

        if ($ttl <= 0) {
            return $callback();
        }

        $payload = $this->cache->remember(
            $this->cacheKey($key),
            now()->addSeconds($ttl),
            fn (): array => ($tenant = $callback())
                ? ['found' => true, 'attributes' => $tenant->getAttributes()]
                : ['found' => false],
        );

        if (! is_array($payload) || ($payload['found'] ?? false) !== true || ! isset($payload['attributes'])) {
            return null;
        }

        $tenant = new Tenant;
        $tenant->setRawAttributes($payload['attributes'], true);
        $tenant->exists = true;

        return $tenant;
    }

    private function cacheKey(string $key): string
    {
        return config('tenancy.cache.prefix', 'tenant').':resolve:v'.self::CACHE_VERSION.':'.$key;
    }
}
