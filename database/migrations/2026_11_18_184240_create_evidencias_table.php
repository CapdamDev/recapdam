<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('evidencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resguardo_id')->constrained()->cascadeOnDelete();
            $table->string('archivo'); // path on the private "local" disk
            $table->string('archivo_nombre_original')->nullable();
            // Captured as typed on the evidence, independent of later edits to the persona/equipo.
            $table->string('numero_serie');
            $table->string('nombre_persona');
            $table->text('descripcion')->nullable();
            $table->foreignId('subido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evidencias');
    }
};
