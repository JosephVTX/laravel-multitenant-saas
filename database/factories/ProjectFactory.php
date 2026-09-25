<?php

namespace Database\Factories;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->catchPhrase();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(100, 9999),
            'description' => fake()->paragraph(),
            'status' => ProjectStatus::Draft->value,
            'meta' => [],
        ];
    }

    /**
     * Attach the project to the given tenant (tenant_id is intentionally guarded).
     */
    public function forTenant(Tenant|int $tenant): static
    {
        $id = $tenant instanceof Tenant ? $tenant->getKey() : $tenant;

        return $this->afterMaking(fn (Project $project) => $project->setAttribute('tenant_id', $id));
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProjectStatus::Active->value,
            'published_at' => now(),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProjectStatus::Archived->value,
        ]);
    }
}
