<?php

namespace App\Models;

use App\Enums\TipoEvidencia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Evidencia extends Model
{
    /** @use HasFactory<\Database\Factories\EvidenciaFactory> */
    use HasFactory;

    protected $fillable = [
        'resguardo_id',
        'tipo',
        'archivo',
        'archivo_nombre_original',
        'numero_serie',
        'nombre_persona',
        'descripcion',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoEvidencia::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Evidencia $evidencia) {
            $evidencia->subido_por ??= auth()->id();
        });

        // Remove the stored file when the record is deleted or the file is replaced.
        static::deleted(function (Evidencia $evidencia) {
            Storage::disk('local')->delete($evidencia->archivo);
        });

        static::updated(function (Evidencia $evidencia) {
            if ($evidencia->wasChanged('archivo') && $evidencia->getOriginal('archivo')) {
                Storage::disk('local')->delete($evidencia->getOriginal('archivo'));
            }
        });
    }

    /** @return BelongsTo<Resguardo, $this> */
    public function resguardo(): BelongsTo
    {
        return $this->belongsTo(Resguardo::class);
    }

    /** @return BelongsTo<User, $this> */
    public function subidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subido_por');
    }
}
