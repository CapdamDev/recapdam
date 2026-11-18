<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TipoEvidencia: string implements HasColor, HasLabel
{
    case FotoSerie = 'foto_serie';
    case ResguardoFirmado = 'resguardo_firmado';
    case Otro = 'otro';

    public function getLabel(): string
    {
        return match ($this) {
            self::FotoSerie => 'Foto del número de serie',
            self::ResguardoFirmado => 'Resguardo firmado',
            self::Otro => 'Otro',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::FotoSerie => 'info',
            self::ResguardoFirmado => 'success',
            self::Otro => 'gray',
        };
    }
}
