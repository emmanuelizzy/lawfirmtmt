<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Task $task): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Super Admin', 'Admin', 'Operations Manager']);
    }

    public function update(User $user, Task $task): bool
    {
        return $user->hasAnyRole(['Super Admin', 'Admin', 'Operations Manager']);
    }

    public function updateStatus(User $user, Task $task): bool
    {
        if ($user->hasAnyRole(['Super Admin', 'Admin', 'Operations Manager'])) {
            return true;
        }

        return $user->hasRole('Staff') && $task->assigned_to === $user->id;
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->hasAnyRole(['Super Admin', 'Admin', 'Operations Manager']);
    }
}
