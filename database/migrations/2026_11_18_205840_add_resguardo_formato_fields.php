<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fields needed to print the official CAPDAM resguardo formats (vehículos and bienes)
 * and to track their paper signatures.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            // Our own category (Vehículo, Laptop, ...). "clase"/"subtipo" below are the patrimonio catalog values.
            $table->renameColumn('tipo', 'categoria');
        });

        Schema::table('equipos', function (Blueprint $table) {
            $table->string('numero_inventario')->nullable()->unique()->after('id');
            $table->string('clasificacion')->nullable()->after('numero_inventario');
            $table->string('clase')->nullable()->after('categoria');
            $table->string('subtipo')->nullable()->after('clase');
            $table->string('estado_conservacion')->nullable();

            // Adquisición
            $table->string('factura')->nullable();
            $table->date('fecha_adquisicion')->nullable();
            $table->string('proveedor')->nullable();
            $table->decimal('importe', 12, 2)->nullable();

            // Vehicle only. For vehicles, "modelo" holds the línea (e.g. ESTACA NP300) and "anio" the model year.
            $table->string('placa')->nullable()->unique();
            $table->string('numero_economico')->nullable();
            $table->unsignedSmallInteger('anio')->nullable();
            $table->unsignedTinyInteger('cilindros')->nullable();
            $table->string('color')->nullable();
            $table->string('capacidad')->nullable();
            $table->string('combustible')->nullable();
            $table->text('equipo_instalado')->nullable();
        });

        Schema::table('resguardos', function (Blueprint $table) {
            $table->string('adscripcion')->nullable()->after('equipo_id');
            // Vo.Bo. of the receiving area's director (vehicles only), e.g. "DIRECTOR DE OPERACION".
            $table->string('director_area_cargo')->nullable()->after('adscripcion');
            $table->string('director_area_nombre')->nullable()->after('director_area_cargo');
        });

        Schema::table('evidencias', function (Blueprint $table) {
            $table->string('tipo')->default('foto_serie')->after('resguardo_id'); // App\Enums\TipoEvidencia
        });

        // Default signer for the fixed roles (Director Administrativo, Jefe de Bienes Patrimoniales).
        Schema::create('firmantes', function (Blueprint $table) {
            $table->id();
            $table->string('rol')->unique(); // App\Enums\RolFirma
            $table->string('nombre');
            $table->timestamps();
        });

        // One row per required signature on a resguardo. Name and position are a snapshot
        // taken while unsigned, so later staff changes don't rewrite signed documents.
        Schema::create('firmas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resguardo_id')->constrained()->cascadeOnDelete();
            $table->string('rol'); // App\Enums\RolFirma
            $table->string('nombre')->nullable();
            $table->string('cargo')->nullable();
            $table->date('firmado_en')->nullable();
            $table->foreignId('marcado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['resguardo_id', 'rol']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('firmas');
        Schema::dropIfExists('firmantes');

        Schema::table('evidencias', fn (Blueprint $table) => $table->dropColumn('tipo'));

        Schema::table('resguardos', fn (Blueprint $table) => $table->dropColumn([
            'adscripcion', 'director_area_cargo', 'director_area_nombre',
        ]));

        Schema::table('equipos', function (Blueprint $table) {
            $table->dropUnique(['numero_inventario']);
            $table->dropUnique(['placa']);
            $table->dropColumn([
                'numero_inventario', 'clasificacion', 'clase', 'subtipo', 'estado_conservacion',
                'factura', 'fecha_adquisicion', 'proveedor', 'importe',
                'placa', 'numero_economico', 'anio', 'cilindros', 'color', 'capacidad', 'combustible', 'equipo_instalado',
            ]);
        });

        Schema::table('equipos', fn (Blueprint $table) => $table->renameColumn('categoria', 'tipo'));
    }
};
