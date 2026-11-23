<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CAPDAM's catalog of bienes (partidas): a 6-level hierarchy
 * Grupo-Subgrupo-Clase-Subclase-Tipo-Producto, imported from CSV by App\Support\ImportadorPartidas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partidas', function (Blueprint $table) {
            $table->id();
            // Codes are NOT unique in the source catalog (e.g. 5-6-2-3-4-1 is used by 4 products).
            $table->string('codigo', 40)->index(); // "5-1-1-1-1-1"
            $table->unsignedTinyInteger('grupo');
            $table->unsignedTinyInteger('subgrupo');
            $table->unsignedTinyInteger('clase');
            $table->unsignedTinyInteger('subclase');
            $table->unsignedTinyInteger('tipo');
            $table->unsignedTinyInteger('producto');
            $table->unsignedTinyInteger('nivel'); // 1 = grupo … 6 = producto
            $table->string('descripcion');
            $table->string('ruta', 1000)->nullable(); // ancestor descriptions, for display and search
            $table->string('cuenta_contable', 40)->nullable(); // "1.2.4.1.1"
            $table->string('nota')->nullable(); // e.g. "MAL CLASIFICADO", "DUPLICADO"
            $table->boolean('seleccionable')->default(false); // leaf of the tree: can be assigned to an equipo
            $table->boolean('activo')->default(true); // false when a later import no longer contains it
            $table->timestamps();

            $table->index(['grupo', 'subgrupo']);
            $table->index(['seleccionable', 'activo']);
        });

        Schema::table('equipos', function (Blueprint $table) {
            $table->foreignId('partida_id')->nullable()->after('id')->constrained()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('partida_id');
        });

        Schema::dropIfExists('partidas');
    }
};
