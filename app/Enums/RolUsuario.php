<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum RolUsuario: string implements HasColor, HasDescription, HasLabel
{
    case Admin = 'admin';
    case Patrimonio = 'patrimonio';

    public function getLabel(): string
    {
        return match ($this) {
            self::Admin => 'Root Admin',
            self::Patrimonio => 'Patrimonio',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Admin => 'Todo lo de Patrimonio, más eliminar registros, desmarcar firmas, configurar firmantes y administrar usuarios.',
            self::Patrimonio => 'Registra personas, equipos y resguardos, sube evidencias, marca firmas y descarga PDFs.',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Admin => 'danger',
            self::Patrimonio => 'info',
        };
    }
}
