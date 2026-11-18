<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('rol')->default('patrimonio')->after('email'); // App\Enums\RolUsuario
            $table->boolean('activo')->default(true)->after('rol');
        });

        // Accounts that existed before roles were created with make:filament-user, so they are admins.
        DB::table('users')->update(['rol' => 'admin']);
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['rol', 'activo']));
    }
};
