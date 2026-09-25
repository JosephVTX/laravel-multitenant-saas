<?php

namespace Tests\Feature\Admin;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TenantManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_non_super_admin_cannot_access_the_admin_panel(): void
    {
        $user = User::factory()->forTenant(Tenant::factory()->create())->create();

        $this->actingAs($user, 'api')
            ->getJson('/api/v1/admin/tenants')
            ->assertForbidden();
    }

    public function test_super_admin_can_create_a_tenant_with_an_owner(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $response = $this->actingAs($admin, 'api')->postJson('/api/v1/admin/tenants', [
            'name' => 'Acme Corp',
            'plan' => 'pro',
            'owner' => [
                'name' => 'Acme Owner',
                'email' => 'owner@acme.test',
                'password' => 'secret-password',
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.slug', 'acme-corp')
            ->assertJsonPath('data.users_count', 1);

        $tenant = Tenant::where('slug', 'acme-corp')->firstOrFail();
        $owner = User::where('email', 'owner@acme.test')->firstOrFail();

        $this->assertSame($tenant->getKey(), $owner->tenant_id);

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->getKey());
        $this->assertTrue($owner->hasRole('tenant-admin'));
    }

    public function test_super_admin_can_filter_tenants_by_slug(): void
    {
        $admin = User::factory()->superAdmin()->create();
        Tenant::factory()->create(['slug' => 'alpha']);
        Tenant::factory()->create(['slug' => 'beta']);

        $this->actingAs($admin, 'api')
            ->getJson('/api/v1/admin/tenants?filter[slug]=beta')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'beta');
    }

    public function test_an_unknown_filter_is_rejected(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin, 'api')
            ->getJson('/api/v1/admin/tenants?filter[hacked]=1')
            ->assertStatus(400)
            ->assertJsonPath('code', 'invalid_query');
    }

    public function test_super_admin_can_suspend_and_activate_a_tenant(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $tenant = Tenant::factory()->create();

        $this->actingAs($admin, 'api')
            ->postJson("/api/v1/admin/tenants/{$tenant->uuid}/suspend")
            ->assertOk()
            ->assertJsonPath('data.status', TenantStatus::Suspended->value);

        $this->assertNotNull($tenant->fresh()->suspended_at);

        $this->actingAs($admin, 'api')
            ->postJson("/api/v1/admin/tenants/{$tenant->uuid}/activate")
            ->assertOk()
            ->assertJsonPath('data.status', TenantStatus::Active->value);

        $this->assertNull($tenant->fresh()->suspended_at);
    }

    public function test_super_admin_can_provision_a_user_inside_a_tenant(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $tenant = Tenant::factory()->create();

        $response = $this->actingAs($admin, 'api')->postJson("/api/v1/admin/tenants/{$tenant->uuid}/users", [
            'name' => 'Tenant Member',
            'email' => 'member@tenant.test',
            'password' => 'secret-password',
            'roles' => ['tenant-member'],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.email', 'member@tenant.test')
            ->assertJsonPath('data.tenant_id', $tenant->getKey());

        $member = User::where('email', 'member@tenant.test')->firstOrFail();

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->getKey());
        $this->assertTrue($member->hasRole('tenant-member'));
    }

    public function test_super_admin_can_read_platform_statistics(): void
    {
        $admin = User::factory()->superAdmin()->create();
        Tenant::factory()->count(2)->create();

        $this->actingAs($admin, 'api')
            ->getJson('/api/v1/admin/stats')
            ->assertOk()
            ->assertJsonPath('data.tenants.total', 2);
    }
}
