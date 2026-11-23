<?php

namespace App\Policies;

use App\Models\Partida;
use App\Models\User;

/**
 * The catalog is read-only in the UI; it changes only by importing a new CSV (admins, "Importar catálogo").
 */
class PartidaPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Partida $partida): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Partida $partida): bool
    {
        return false;
    }

    public function delete(User $user, Partida $partida): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function importar(User $user): bool
    {
        return $user->esAdmin();
    }
}
