<?php

namespace App\Models;

use App\Enums\TipoPersona;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Persona extends Model
{
    /** @use HasFactory<\Database\Factories\PersonaFactory> */
    use HasFactory;

    protected $fillable = [
        'nombre',
        'tipo',
        'numero_empleado',
        'departamento',
        'email',
        'telefono',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoPersona::class,
        ];
    }

    /** @return HasMany<Resguardo, $this> */
    public function resguardos(): HasMany
    {
        return $this->hasMany(Resguardo::class);
    }
}
