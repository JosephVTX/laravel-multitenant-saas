<?php

namespace Database\Seeders;

use App\Enums\TenantStatus;
use App\Enums\UserStatus;
use App\Models\Project;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

/**
 * Optional demo tenant used to smoke test the deployment.
 *
 * Disabled unless SEED_DEMO_TENANT is truthy. Never enable it on a real
 * production database with real customers.
 */
class DemoTenantSeeder extends Seeder
{
    public function run(): void
    {
        if (! filter_var(env('SEED_DEMO_TENANT', false), FILTER_VALIDATE_BOOL)) {
            return;
        }

        $slug = (string) env('DEMO_TENANT_SLUG', 'demo');

        $tenant = Tenant::query()->firstOrCreate(
            ['slug' => $slug],
            [
                'name' => (string) env('DEMO_TENANT_NAME', 'Demo Tenant'),
                'status' => TenantStatus::Active->value,
                'plan' => 'pro',
                'settings' => ['features' => ['projects' => true]],
            ],
        );

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->getKey());

        $email = (string) env('DEMO_TENANT_EMAIL', 'admin@demo.test');

        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $user = new User([
                'name' => 'Demo Admin',
                'email' => $email,
                'password' => (string) env('DEMO_TENANT_PASSWORD', 'password'),
                'status' => UserStatus::Active->value,
            ]);

            $user->forceFill([
                'tenant_id' => $tenant->getKey(),
                'email_verified_at' => now(),
            ])->save();
        }

        if (! $user->hasRole('tenant-admin')) {
            $user->assignRole('tenant-admin');
        }

        if ($tenant->projects()->count() === 0) {
            Project::factory()
                ->count(15)
                ->forTenant($tenant)
                ->create();
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
    }
}
