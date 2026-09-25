<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds global (tenant agnostic) permissions and role templates.
 *
 * Roles live with tenant_id = null, so a single set of role definitions is
 * shared by every tenant instead of duplicated per tenant.
 */
class RolePermissionSeeder extends Seeder
{
    public const GUARD = 'api';

    /** @var list<string> */
    public const PERMISSIONS = [
        'tenants.view',
        'tenants.create',
        'tenants.update',
        'tenants.delete',
        'users.view',
        'users.create',
        'users.update',
        'users.delete',
        'roles.manage',
        'projects.view',
        'projects.create',
        'projects.update',
        'projects.delete',
    ];

    /** @var array<string, list<string>> */
    public const ROLES = [
        'super-admin' => self::PERMISSIONS,
        'tenant-admin' => [
            'users.view', 'users.create', 'users.update', 'users.delete',
            'roles.manage',
            'projects.view', 'projects.create', 'projects.update', 'projects.delete',
        ],
        'tenant-manager' => [
            'users.view',
            'projects.view', 'projects.create', 'projects.update',
        ],
        'tenant-member' => [
            'projects.view', 'projects.create',
        ],
    ];

    public function run(): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId(null);
        $registrar->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, self::GUARD);
        }

        foreach (self::ROLES as $role => $permissions) {
            Role::findOrCreate($role, self::GUARD)->syncPermissions($permissions);
        }

        $registrar->forgetCachedPermissions();
    }
}
