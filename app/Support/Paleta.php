<?php

namespace App\Support;

use App\Models\Ajuste;
use Filament\Support\Colors\Color;

/**
 * Turns the Ajustes colors into Filament's 50–950 primary palette.
 */
class Paleta
{
    /** @return array<int, string> */
    public static function primaria(Ajuste $ajuste): array
    {
        $paleta = Color::hex($ajuste->color_principal);

        // Filament fills buttons and links with shade 600, so pin it to the exact institutional color.
        $paleta[600] = $ajuste->color_principal;

        if (filled($ajuste->color_principal_claro)) {
            $paleta[500] = $ajuste->color_principal_claro; // hover and borders
        }

        if (filled($ajuste->color_principal_oscuro)) {
            $paleta[700] = $ajuste->color_principal_oscuro;
            $paleta[800] = $ajuste->color_principal_oscuro;
        }

        return $paleta;
    }
}
