<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Signature boxes on the printed resguardo. Vehicles need all four; other bienes need Entrega and Recibe.
 */
enum RolFirma: string implements HasLabel
{
    case VoBoDirectorAdministrativo = 'vobo_director_administrativo';
    case Entrega = 'entrega';
    case VoBoDirectorArea = 'vobo_director_area';
    case Recibe = 'recibe';

    /** @return list<self> */
    public static function requeridas(bool $esVehiculo): array
    {
        return $esVehiculo
            ? [self::VoBoDirectorAdministrativo, self::Entrega, self::VoBoDirectorArea, self::Recibe]
            : [self::Entrega, self::Recibe];
    }

    /** Roles whose signer is the same person for every resguardo, configured in Firmantes. */
    public static function fijos(): array
    {
        return [self::VoBoDirectorAdministrativo, self::Entrega];
    }

    /** Heading printed above the signature line. */
    public function encabezado(): string
    {
        return match ($this) {
            self::VoBoDirectorAdministrativo, self::VoBoDirectorArea => 'Vo.Bo.',
            self::Entrega => 'Entrega',
            self::Recibe => 'Recibe',
        };
    }

    /** Fixed position for this role, or null when it depends on the resguardo. */
    public function cargo(): ?string
    {
        return match ($this) {
            self::VoBoDirectorAdministrativo => 'DIRECTOR ADMINISTRATIVO',
            self::Entrega => 'JEFE DE BIENES PATRIMONIALES',
            self::VoBoDirectorArea, self::Recibe => null,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::VoBoDirectorAdministrativo => 'Vo.Bo. Director Administrativo',
            self::Entrega => 'Entrega (Jefe de Bienes Patrimoniales)',
            self::VoBoDirectorArea => 'Vo.Bo. Director del área',
            self::Recibe => 'Recibe (usuario del bien)',
        };
    }
}
