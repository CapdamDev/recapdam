<?php

namespace App\Models;

use App\Enums\RolFirma;
use Illuminate\Database\Eloquent\Model;

/**
 * Who currently holds a fixed signing role (see RolFirma::fijos()).
 */
class Firmante extends Model
{
    protected $fillable = ['rol', 'nombre'];

    protected function casts(): array
    {
        return [
            'rol' => RolFirma::class,
        ];
    }

    protected static function booted(): void
    {
        // Pending signatures pick up the new name; signed ones keep the name they were signed with.
        static::saved(function (Firmante $firmante) {
            Firma::query()
                ->where('rol', $firmante->rol)
                ->whereNull('firmado_en')
                ->update(['nombre' => $firmante->nombre]);
        });
    }

    public static function nombrePara(RolFirma $rol): ?string
    {
        return static::query()->where('rol', $rol)->value('nombre');
    }
}
