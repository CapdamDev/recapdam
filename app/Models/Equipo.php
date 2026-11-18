<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Equipo extends Model
{
    /** @use HasFactory<\Database\Factories\EquipoFactory> */
    use HasFactory;

    public const CATEGORIA_VEHICULO = 'Vehículo';

    public const CATEGORIAS = [self::CATEGORIA_VEHICULO, 'Laptop', 'PC', 'Monitor', 'Celular', 'Tablet', 'Impresora', 'Radio', 'Mobiliario', 'Otro'];

    protected $fillable = [
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

    public function esVehiculo(): bool
    {
        return $this->categoria === self::CATEGORIA_VEHICULO;
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
        return trim("{$this->categoria} {$this->marca} {$this->modelo}")." ({$this->numero_serie})";
    }
}
