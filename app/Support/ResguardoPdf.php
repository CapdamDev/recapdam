<?php

namespace App\Support;

use App\Enums\RolFirma;
use App\Models\Resguardo;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Str;

/**
 * Renders the printable CAPDAM resguardo format (vehículos or bienes) for paper signatures.
 */
class ResguardoPdf
{
    public const LOGO_PATH = 'img/logo-capdam.png';

    public static function generar(Resguardo $resguardo): string
    {
        $resguardo->loadMissing(['persona', 'equipo', 'firmas']);

        $orden = RolFirma::cases();
        $firmas = $resguardo->firmas
            ->sortBy(fn ($firma) => array_search($firma->rol, $orden, true))
            ->keyBy(fn ($firma) => $firma->rol->value);

        $logo = public_path(self::LOGO_PATH);

        $html = view('pdf.resguardo', [
            'resguardo' => $resguardo,
            'equipo' => $resguardo->equipo,
            'esVehiculo' => $resguardo->equipo->esVehiculo(),
            'firmas' => $firmas,
            'logo' => is_file($logo) ? 'data:image/png;base64,'.base64_encode(file_get_contents($logo)) : null,
            'generado' => now(),
        ])->render();

        $options = new Options;
        $options->setDefaultFont('DejaVu Sans'); // covers accents and ñ
        $options->setIsRemoteEnabled(false);
        $options->setChroot(base_path());

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('letter');
        $dompdf->render();

        return $dompdf->output();
    }

    public static function nombreArchivo(Resguardo $resguardo): string
    {
        $equipo = $resguardo->equipo;
        $id = $equipo->numero_inventario ?: $equipo->numero_serie;

        return 'resguardo-'.Str::slug(($equipo->esVehiculo() ? 'vehiculo-' : '').$id).'.pdf';
    }
}
