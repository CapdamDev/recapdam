<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Equipo extends Model
{
    /** @use HasFactory<\Database\Factories\EquipoFactory> */
    use HasFactory;

    /** Categoría of equipos registered before the catalog existed; new ones derive it from their partida. */
    public const CATEGORIA_VEHICULO = 'Vehículo';

    protected $fillable = [
        'partida_id',
        'numero_inventario',
        'clasificacion',
        'categoria',
        'clase',
        'subtipo',
        'marca',
        'modelo',
        'numero_serie',
        'descripcion',
        'estado_conservacion',
        'factura',
        'fecha_adquisicion',
        'proveedor',
        'importe',
        'placa',
        'numero_economico',
        'anio',
        'cilindros',
        'color',
        'capacidad',
        'combustible',
        'equipo_instalado',
    ];

    protected function casts(): array
    {
        return [
            'fecha_adquisicion' => 'date',
            'importe' => 'decimal:2',
            'anio' => 'integer',
            'cilindros' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // The categoría is the partida's subgrupo (e.g. "MOBILIARIO Y EQUIPO DE ADMINISTRACION").
        static::saving(function (Equipo $equipo) {
            if ($equipo->partida_id && ($equipo->isDirty('partida_id') || blank($equipo->categoria))) {
                $equipo->categoria = Partida::find($equipo->partida_id)?->nombreGrupo();
            }
        });
    }

    /** @return BelongsTo<Partida, $this> */
    public function partida(): BelongsTo
    {
        return $this->belongsTo(Partida::class);
    }

    public function esVehiculo(): bool
    {
        return $this->partida_id
            ? (bool) $this->partida?->esVehiculo()
            : $this->categoria === self::CATEGORIA_VEHICULO;
    }

    /** @return HasMany<Resguardo, $this> */
    public function resguardos(): HasMany
    {
        return $this->hasMany(Resguardo::class);
    }

    /**
     * The open assignment (no return date), if the device is currently assigned.
     *
     * @return HasOne<Resguardo, $this>
     */
    public function resguardoActivo(): HasOne
    {
        return $this->hasOne(Resguardo::class)->whereNull('fecha_devolucion')->latestOfMany('fecha_asignacion');
    }

    public function getEtiquetaAttribute(): string
    {
        return trim(($this->partida->descripcion ?? $this->categoria)." {$this->marca} {$this->modelo}")." ({$this->numero_serie})";
    }
}
