<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'status' => 'active',
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Attach the user to the given tenant (tenant_id is intentionally guarded).
     */
    public function forTenant(Tenant|int $tenant): static
    {
        $id = $tenant instanceof Tenant ? $tenant->getKey() : $tenant;

        return $this->afterMaking(fn (User $user) => $user->setAttribute('tenant_id', $id));
    }

    /**
     * Create a platform level (central) super administrator.
     */
    public function superAdmin(): static
    {
        return $this->afterMaking(function (User $user): void {
            $user->setAttribute('tenant_id', null);
            $user->setAttribute('is_super_admin', true);
        });
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'inactive']);
    }
}
