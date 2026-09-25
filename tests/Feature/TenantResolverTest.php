<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Support\Tenancy\TenantResolver;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TenantResolverTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_second_resolution_is_served_from_cache_without_queries(): void
    {
        $tenant = Tenant::factory()->create();
        $resolver = app(TenantResolver::class);

        $resolver->findById($tenant->getKey());

        DB::enableQueryLog();
        $resolved = $resolver->findById($tenant->getKey());
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertInstanceOf(Tenant::class, $resolved);
        $this->assertSame($tenant->getKey(), $resolved->getKey());
        $this->assertSame($tenant->slug, $resolved->slug);
        $this->assertTrue($resolved->isActive());
        $this->assertCount(0, $queries);
    }

    public function test_a_missing_tenant_is_cached_as_null(): void
    {
        $resolver = app(TenantResolver::class);

        $this->assertNull($resolver->findById(99999));

        DB::enableQueryLog();
        $this->assertNull($resolver->findById(99999));
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertCount(0, $queries);
    }

    public function test_updating_a_tenant_invalidates_the_cached_resolution(): void
    {
        $tenant = Tenant::factory()->create(['slug' => 'before']);
        $resolver = app(TenantResolver::class);

        $this->assertSame('before', $resolver->findById($tenant->getKey())?->slug);

        $tenant->update(['slug' => 'after']);

        $this->assertSame('after', $resolver->findById($tenant->getKey())?->slug);
    }
}
