<?php

namespace Tests\Feature\Tenant;

use App\Models\Project;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ProjectIsolationTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_a_tenant_only_lists_its_own_projects(): void
    {
        [$tenantA, $userA] = $this->tenantWithAdmin();
        [$tenantB] = $this->tenantWithAdmin();

        Project::factory()->count(3)->forTenant($tenantA)->create();
        Project::factory()->count(5)->forTenant($tenantB)->create();

        $this->actingAs($userA, 'api')
            ->getJson('/api/v1/tenant/projects')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_creating_a_project_auto_assigns_the_current_tenant(): void
    {
        [$tenant, $user] = $this->tenantWithAdmin();

        $this->actingAs($user, 'api')
            ->postJson('/api/v1/tenant/projects', ['name' => 'New Project'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'New Project');

        $project = Project::withoutTenantScope()->where('name', 'New Project')->firstOrFail();
        $this->assertSame($tenant->getKey(), $project->tenant_id);
    }

    public function test_a_tenant_cannot_view_another_tenants_project(): void
    {
        [, $userA] = $this->tenantWithAdmin();
        [$tenantB] = $this->tenantWithAdmin();
        $foreign = Project::factory()->forTenant($tenantB)->create();

        $this->actingAs($userA, 'api')
            ->getJson("/api/v1/tenant/projects/{$foreign->uuid}")
            ->assertNotFound();
    }

    public function test_a_tenant_cannot_update_or_delete_another_tenants_project(): void
    {
        [, $userA] = $this->tenantWithAdmin();
        [$tenantB] = $this->tenantWithAdmin();
        $foreign = Project::factory()->forTenant($tenantB)->create();

        $this->actingAs($userA, 'api')
            ->putJson("/api/v1/tenant/projects/{$foreign->uuid}", ['name' => 'Hacked'])
            ->assertNotFound();

        $this->actingAs($userA, 'api')
            ->deleteJson("/api/v1/tenant/projects/{$foreign->uuid}")
            ->assertNotFound();

        $this->assertSame($foreign->fresh()->name !== 'Hacked', true);
    }

    public function test_a_user_without_project_permission_is_forbidden(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->forTenant($tenant)->create();

        $this->actingAs($user, 'api')
            ->getJson('/api/v1/tenant/projects')
            ->assertForbidden();
    }

    public function test_project_validation_rejects_an_empty_payload(): void
    {
        [, $user] = $this->tenantWithAdmin();

        $this->actingAs($user, 'api')
            ->postJson('/api/v1/tenant/projects', [])
            ->assertStatus(422)
            ->assertJsonPath('code', 'validation_error')
            ->assertJsonValidationErrors(['name']);
    }

    /**
     * @return array{0: Tenant, 1: User}
     */
    private function tenantWithAdmin(): array
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->forTenant($tenant)->create();

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->getKey());
        $user->assignRole('tenant-admin');

        return [$tenant, $user];
    }
}
