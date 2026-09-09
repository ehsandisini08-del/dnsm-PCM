<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->is_active) {
            return false;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'dns_admin', 'operator'], true);
    }

    public function view(User $user, User $model): bool
    {
        return in_array($user->role, ['super_admin', 'dns_admin', 'operator'], true);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'dns_admin'], true);
    }

    public function update(User $user, User $model): bool
    {
        // DNS admin can update operators and customers, or themselves
        if ($user->isDnsAdmin()) {
            return ! $model->isSuperAdmin();
        }

        // Users can always update their own profile
        return $user->id === $model->id;
    }

    public function delete(User $user, User $model): bool
    {
        // Only Super Admin can delete users; operator and dns_admin cannot
        if ($user->isSuperAdmin()) {
            // Cannot delete yourself
            return $user->id !== $model->id;
        }

        return false;
    }
}
