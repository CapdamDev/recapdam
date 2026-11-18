<?php

namespace App\Policies;

use App\Models\User;

/**
 * Firmantes are configuration: admins only.
 */
class FirmantePolicy
{
    public function before(User $user): ?bool
    {
        return $user->esAdmin() ? null : false;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user): bool
    {
        return true;
    }

    public function delete(User $user): bool
    {
        return true;
    }
}
