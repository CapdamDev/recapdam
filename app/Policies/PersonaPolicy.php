<?php

namespace App\Policies;

use App\Models\Persona;
use App\Models\User;

/**
 * Both roles register and edit; only admins delete.
 */
class PersonaPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Persona $record): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Persona $record): bool
    {
        return true;
    }

    public function delete(User $user, Persona $record): bool
    {
        return $user->esAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->esAdmin();
    }
}
