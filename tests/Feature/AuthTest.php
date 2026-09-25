<?php

namespace Tests\Feature;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_user_can_login_and_receives_a_token(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->forTenant($tenant)->create(['password' => 'secret-password']);

        $this->assignRole($user, $tenant, 'tenant-admin');

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'secret-password',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_type', 'bearer')
            ->assertJsonPath('data.user.email', $user->email)
            ->assertJsonStructure(['data' => ['access_token', 'expires_in']]);

        $this->assertNotEmpty($response->json('data.access_token'));
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $user = User::factory()->forTenant(Tenant::factory()->create())->create();

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertStatus(401)->assertJsonPath('code', 'invalid_credentials');
    }

    public function test_login_fails_for_inactive_user(): void
    {
        $user = User::factory()->forTenant(Tenant::factory()->create())->inactive()->create([
            'password' => 'secret-password',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'secret-password',
        ])->assertStatus(403)->assertJsonPath('code', 'user_inactive');
    }

    public function test_login_fails_for_inactive_tenant(): void
    {
        $tenant = Tenant::factory()->create(['status' => TenantStatus::Inactive]);
        $user = User::factory()->forTenant($tenant)->create(['password' => 'secret-password']);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'secret-password',
        ])->assertStatus(403)->assertJsonPath('code', 'tenant_inactive');
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/v1/auth/me')->assertStatus(401)->assertJsonPath('code', 'unauthenticated');
    }

    public function test_me_returns_the_authenticated_user_with_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->forTenant($tenant)->create();

        $this->actingAs($user, 'api')
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.uuid', $user->uuid)
            ->assertJsonPath('data.tenant.uuid', $tenant->uuid);
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->forTenant(Tenant::factory()->create())->create(['password' => 'secret-password']);

        $token = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'secret-password',
        ])->json('data.access_token');

        $this->withToken($token)
            ->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    private function assignRole(User $user, Tenant $tenant, string $role): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->getKey());
        $user->assignRole($role);
    }
}
