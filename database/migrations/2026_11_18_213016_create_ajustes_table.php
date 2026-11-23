<?php

use App\Models\Ajuste;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Single-row table with the system's branding: name, logo, favicon and color palette.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ajustes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_sistema');
            $table->string('logo')->nullable();    // path on the "public" disk
            $table->string('favicon')->nullable(); // path on the "public" disk
            $table->string('color_principal', 7);
            $table->string('color_acento', 7);
            $table->string('color_principal_claro', 7)->nullable();  // null = derived from principal
            $table->string('color_principal_oscuro', 7)->nullable(); // null = derived from principal
            $table->string('color_texto', 7);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ajustes');
    }
};
