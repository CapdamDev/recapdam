<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * One entry of the catalog of bienes. Only leaves (seleccionable) are assigned to equipos.
 */
class Partida extends Model
{
    /** @use HasFactory<\Database\Factories\PartidaFactory> */
    use HasFactory;

    /** Grupo-subgrupo of "VEHICULOS Y EQUIPO DE TRANSPORTE". */
    public const VEHICULOS = [5, 4];

    protected $fillable = [
        'codigo', 'grupo', 'subgrupo', 'clase', 'subclase', 'tipo', 'producto', 'nivel',
        'descripcion', 'ruta', 'cuenta_contable', 'nota', 'seleccionable', 'activo',
    ];

    protected function casts(): array
    {
        return [
            'seleccionable' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    /** @return HasMany<Equipo, $this> */
    public function equipos(): HasMany
    {
        return $this->hasMany(Equipo::class);
    }

    public function esVehiculo(): bool
    {
        return [$this->grupo, $this->subgrupo] === self::VEHICULOS;
    }

    /** Name of the subgrupo (e.g. "MOBILIARIO Y EQUIPO DE ADMINISTRACION"), used as the equipo's categoría. */
    public function nombreGrupo(): string
    {
        return Str::before($this->ruta ?? '', ' › ') ?: $this->descripcion;
    }

    /** @param Builder<Partida> $query */
    public function scopeSeleccionables(Builder $query): void
    {
        $query->where('seleccionable', true)->where('activo', true);
    }

    /**
     * Every word must appear in the description, the path or the code, in any order.
     * The DB collation ignores case and accents, so "estanteria" finds "ESTANTERÍA".
     *
     * @param Builder<Partida> $query
     */
    public function scopeBuscar(Builder $query, ?string $texto): void
    {
        $palabras = preg_split('/\s+/', trim((string) $texto), -1, PREG_SPLIT_NO_EMPTY);

        if ($palabras !== []) {
            // Most relevant first: description starts with the first word, then contains it, then path/code matches.
            $primera = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $palabras[0]);
            $query->orderByRaw(
                'CASE WHEN descripcion LIKE ? THEN 0 WHEN descripcion LIKE ? THEN 1 ELSE 2 END',
                ["{$primera}%", "%{$primera}%"],
            );
        }

        foreach (array_slice($palabras, 0, 8) as $palabra) {
            $escapada = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $palabra);

            $query->where(fn (Builder $q) => $q
                ->where('descripcion', 'like', "%{$escapada}%")
                ->orWhere('ruta', 'like', "%{$escapada}%")
                ->orWhere('codigo', 'like', "{$escapada}%"));
        }
    }

    /** Option label for the product search: description, code and path. */
    public function etiquetaHtml(): string
    {
        $nota = $this->nota ? ' <span style="color:#b45309">· '.e($this->nota).'</span>' : '';

        return '<div><span style="font-weight:600">'.e($this->descripcion).'</span>'
            .' <span style="opacity:.6;font-family:monospace">'.e($this->codigo).'</span>'.$nota
            .'<div style="font-size:.8em;opacity:.65">'.e($this->ruta).'</div></div>';
    }
}
