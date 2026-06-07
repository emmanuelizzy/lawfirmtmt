<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Project;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Project $project): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Super Admin', 'Admin', 'Operations Manager']);
    }

    public function update(User $user, Project $project): bool
    {
        return $user->hasAnyRole(['Super Admin', 'Admin', 'Operations Manager']);
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->hasAnyRole(['Super Admin', 'Admin']);
    }
}
