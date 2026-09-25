<?php

namespace Database\Seeders;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) env('SUPER_ADMIN_EMAIL', 'admin@saas.ndn.pe');

        if (User::query()->where('email', $email)->exists()) {
            return;
        }

        $user = new User([
            'name' => (string) env('SUPER_ADMIN_NAME', 'Super Admin'),
            'email' => $email,
            'password' => (string) env('SUPER_ADMIN_PASSWORD', 'password'),
            'status' => UserStatus::Active->value,
        ]);

        $user->forceFill([
            'tenant_id' => null,
            'is_super_admin' => true,
            'email_verified_at' => now(),
        ])->save();
    }
}
