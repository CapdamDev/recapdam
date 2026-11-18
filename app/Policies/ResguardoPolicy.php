<?php

namespace App\Policies;

use App\Models\Resguardo;
use App\Models\User;

/**
 * Both roles register and edit; only admins delete.
 */
class ResguardoPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Resguardo $record): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Resguardo $record): bool
    {
        return true;
    }

    public function delete(User $user, Resguardo $record): bool
    {
        return $user->esAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->esAdmin();
    }
}
