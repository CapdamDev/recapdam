<?php

namespace App\Policies;

use App\Models\User;

/**
 * Only admins manage accounts. Accounts are deactivated, never deleted, so audit fields
 * (registrado_por, subido_por, marcado_por) keep pointing at real users.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->esAdmin();
    }

    public function view(User $user, User $model): bool
    {
        return $user->esAdmin();
    }

    public function create(User $user): bool
    {
        return $user->esAdmin();
    }

    public function update(User $user, User $model): bool
    {
        return $user->esAdmin();
    }

    public function delete(User $user, User $model): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
