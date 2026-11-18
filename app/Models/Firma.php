<?php

namespace App\Models;

use App\Enums\RolFirma;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Firma extends Model
{
    protected $fillable = ['rol', 'nombre', 'cargo', 'firmado_en', 'marcado_por'];

    protected function casts(): array
    {
        return [
            'rol' => RolFirma::class,
            'firmado_en' => 'date',
        ];
    }

    /** @return BelongsTo<Resguardo, $this> */
    public function resguardo(): BelongsTo
    {
        return $this->belongsTo(Resguardo::class);
    }

    /** @return BelongsTo<User, $this> */
    public function marcadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marcado_por');
    }

    public function estaFirmada(): bool
    {
        return $this->firmado_en !== null;
    }
}
