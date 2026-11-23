<?php

namespace App\Console\Commands;

use App\Enums\RolUsuario;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * make:filament-user creates Patrimonio users; this promotes one to Root Admin (e.g. the first user on a new server).
 */
#[Signature('usuarios:hacer-admin {email : Correo del usuario}')]
#[Description('Convierte un usuario existente en Root Admin y lo activa')]
class HacerAdmin extends Command
{
    public function handle(): int
    {
        $user = User::firstWhere('email', $this->argument('email'));

        if (! $user) {
            $this->error('No existe un usuario con ese correo. Créalo primero con php artisan make:filament-user.');

            return self::FAILURE;
        }

        $user->update(['rol' => RolUsuario::Admin, 'activo' => true]);
        $this->info("{$user->email} ahora es Root Admin.");

        return self::SUCCESS;
    }
}
