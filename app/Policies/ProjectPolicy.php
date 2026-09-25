<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('projects.view');
    }

    public function view(User $user, Project $project): bool
    {
        return $user->can('projects.view') && $this->ownsTenant($user, $project);
    }

    public function create(User $user): bool
    {
        return $user->can('projects.create');
    }

    public function update(User $user, Project $project): bool
    {
        return $user->can('projects.update') && $this->ownsTenant($user, $project);
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->can('projects.delete') && $this->ownsTenant($user, $project);
    }

    protected function ownsTenant(User $user, Project $project): bool
    {
        return $user->isSuperAdmin() || (int) $user->tenant_id === (int) $project->tenant_id;
    }
}
