<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

/**
 * Permission names are checked against the active tenant (spatie teams).
 * Super admins bypass every check through the Gate::before hook.
 */
class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('projects.view');
    }

    public function view(User $user, Project $project): bool
    {
        return $user->can('projects.view');
    }

    public function create(User $user): bool
    {
        return $user->can('projects.create');
    }

    public function update(User $user, Project $project): bool
    {
        return $user->can('projects.update');
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->can('projects.delete');
    }
}
