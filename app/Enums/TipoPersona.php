<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TipoPersona: string implements HasColor, HasLabel
{
    case Empleado = 'empleado';
    case Cliente = 'cliente';

    public function getLabel(): string
    {
        return match ($this) {
            self::Empleado => 'Empleado',
            self::Cliente => 'Cliente',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Empleado => 'info',
            self::Cliente => 'warning',
        };
    }
}
