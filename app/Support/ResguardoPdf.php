<?php

namespace App\Support;

use App\Enums\RolFirma;
use App\Models\Ajuste;
use App\Models\Resguardo;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Str;

/**
 * Renders the printable CAPDAM resguardo format (vehículos or bienes) for paper signatures.
 */
class ResguardoPdf
{
    public static function generar(Resguardo $resguardo): string
    {
        $resguardo->loadMissing(['persona', 'equipo.partida', 'firmas']);

        $orden = RolFirma::cases();
        $firmas = $resguardo->firmas
            ->sortBy(fn ($firma) => array_search($firma->rol, $orden, true))
            ->keyBy(fn ($firma) => $firma->rol->value);

        $ajuste = Ajuste::actual();
        $logo = $ajuste->logoPath();

        $html = view('pdf.resguardo', [
            'resguardo' => $resguardo,
            'equipo' => $resguardo->equipo,
            'esVehiculo' => $resguardo->equipo->esVehiculo(),
            'firmas' => $firmas,
            'logo' => $logo ? 'data:'.mime_content_type($logo).';base64,'.base64_encode(file_get_contents($logo)) : null,
            'nombreSistema' => $ajuste->nombre_sistema,
            'generado' => now(),
        ])->render();

        $options = new Options;
        $options->setDefaultFont('DejaVu Sans'); // covers accents and ñ
        $options->setIsRemoteEnabled(false);
        $options->setChroot(base_path());
        // vendor/ isn't writable by the web server in the Docker image, so keep dompdf's font cache in storage.
        $fuentes = storage_path('fonts');
        is_dir($fuentes) || mkdir($fuentes, 0775, true);
        $options->setFontCache($fuentes);

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
