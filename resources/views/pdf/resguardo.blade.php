@php
    use App\Enums\RolFirma;

    $v = fn ($value) => filled($value) ? mb_strtoupper((string) $value) : '';
    $firma = fn (RolFirma $rol) => $firmas->get($rol->value);
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Resguardo</title>
<style>
    @page { margin: 28px 34px 40px; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 8px; color: #000; }
    table { width: 100%; border-collapse: collapse; }
    td { vertical-align: top; }

    .header td { vertical-align: middle; }
    .org { text-align: center; font-weight: bold; }
    .org .l1 { font-size: 12px; }
    .org .l2 { font-size: 10.5px; }
    .org .l3 { font-size: 8.5px; }
    .title { text-align: center; font-weight: bold; font-size: 15px; margin: 6px 0 10px; }

    .box { border: 1px solid #555; }
    .box td { border: 1px solid #555; padding: 1px 4px; text-align: center; height: 11px; }
    .box .h { background: #c8c8c8; font-weight: bold; }

    .section { font-weight: bold; font-style: italic; font-size: 8.5px; margin: 10px 0 2px; }
    .grid { border: 1px solid #555; }
    .grid td { border-left: 1px solid #555; border-right: 1px solid #555; padding: 1px 4px; }
    .grid .h td { background: #c8c8c8; border-top: 1px solid #555; }
    .grid .val td { height: 11px; }
    .obs { border: 1px solid #555; height: 90px; padding: 4px; }

    .legal { border-top: 3px solid #888; border-bottom: 3px solid #888; margin-top: 10px; padding: 6px 30px;
             text-align: center; font-weight: bold; font-style: italic; font-size: 8.5px; line-height: 1.6; }

    .firmas { margin-top: 14px; }
    .firmas td { width: 50%; text-align: center; padding: 0 30px; height: 95px; }
    .firmas .enc { font-weight: bold; font-size: 10px; }
    .firmas .cargo { margin-top: 2px; height: 10px; }
    .firmas .linea { border-top: 1px solid #000; margin-top: 36px; padding-top: 2px; }

    .footer { position: fixed; bottom: -24px; left: 0; right: 0; border-top: 1px solid #000; padding-top: 2px; font-size: 7.5px; font-weight: bold; }
</style>
</head>
<body>

<table class="header">
    <tr>
        <td style="width: 18%; text-align: center;">
            @if ($logo)
                <img src="{{ $logo }}" style="max-width: 90px; max-height: 70px;">
            @endif
        </td>
        <td class="org" style="width: 56%;">
            <div class="l1">COM DE AGUA POT DRENAJE Y ALC DE</div>
            <div class="l2">DEPENDENCIA CONTROL PATRIMONIAL</div>
            <div class="l3">DEPARTAMENTO DE CONTROL PATRIMONIAL</div>
        </td>
        <td style="width: 26%;">
            <table class="box">
                <tr><td class="h">No. Inventario</td></tr>
                <tr><td>{{ $equipo->numero_inventario }}</td></tr>
                <tr><td class="h">Clasificación</td></tr>
                <tr><td>{{ $equipo->clasificacion }}</td></tr>
                <tr><td class="h">Fecha de Emisión</td></tr>
                <tr><td>{{ $resguardo->fecha_asignacion->format('d/m/Y') }}</td></tr>
            </table>
        </td>
    </tr>
</table>

<div class="title">{{ $esVehiculo ? 'RESGUARDO DE VEHICULOS' : 'RESGUARDO DE BIENES' }}</div>

<div class="section">Datos Generales</div>
@if ($esVehiculo)
    <table class="grid">
        <tr class="h"><td style="width: 16%">Placa</td><td style="width: 18%">No. Económico</td><td style="width: 9%">Modelo</td><td colspan="3">Descripción</td></tr>
        <tr class="val"><td>{{ $v($equipo->placa) }}</td><td>{{ $v($equipo->numero_economico) }}</td><td>{{ $equipo->anio }}</td><td colspan="3" rowspan="3">{{ $v($equipo->descripcion) }}</td></tr>
        <tr class="h"><td>Clase</td><td colspan="2">Tipo</td></tr>
        <tr class="val"><td>{{ $v($equipo->clase) }}</td><td colspan="2">{{ $v($equipo->subtipo) }}</td></tr>
        <tr class="h"><td>Marca</td><td colspan="2">Línea</td><td>No. de Serie</td><td style="width: 12%">Cilindros</td><td style="width: 14%">Color</td></tr>
        <tr class="val"><td>{{ $v($equipo->marca) }}</td><td colspan="2">{{ $v($equipo->modelo) }}</td><td>{{ $v($equipo->numero_serie) }}</td><td style="text-align:center">{{ $equipo->cilindros }}</td><td>{{ $v($equipo->color) }}</td></tr>
        <tr class="h"><td>Capacidad</td><td colspan="2">Combustible</td><td colspan="3">Equipo Instalado</td></tr>
        <tr class="val"><td>{{ $v($equipo->capacidad) }}</td><td colspan="2" rowspan="3">{{ $v($equipo->combustible) }}</td><td colspan="3" rowspan="3">{{ $v($equipo->equipo_instalado) }}</td></tr>
        <tr class="h"><td>Edo. de Conservación</td></tr>
        <tr class="val"><td>{{ $v($equipo->estado_conservacion) }}</td></tr>
        <tr class="h"><td>Factura</td><td colspan="2">Fecha de Adquisición</td><td colspan="2">Proveedor</td><td>Importe</td></tr>
        <tr class="val"><td>{{ $v($equipo->factura) }}</td><td colspan="2" style="text-align:center">{{ $equipo->fecha_adquisicion?->format('d/m/Y') }}</td><td colspan="2">{{ $v($equipo->proveedor) }}</td><td>{{ $equipo->importe !== null ? '$'.number_format($equipo->importe, 2) : '' }}</td></tr>
    </table>
@else
    <table class="grid">
        <tr class="h"><td style="width: 22%">Producto</td><td style="width: 22%">Marca</td><td style="width: 22%">Modelo</td><td>No. de Serie</td></tr>
        <tr class="val"><td>{{ $v($equipo->partida->descripcion ?? $equipo->categoria) }}</td><td>{{ $v($equipo->marca) }}</td><td>{{ $v($equipo->modelo) }}</td><td>{{ $v($equipo->numero_serie) }}</td></tr>
        <tr class="h"><td>Clase</td><td>Tipo</td><td>Edo. de Conservación</td><td>Descripción</td></tr>
        <tr class="val"><td>{{ $v($equipo->clase) }}</td><td>{{ $v($equipo->subtipo) }}</td><td>{{ $v($equipo->estado_conservacion) }}</td><td>{{ $v($equipo->descripcion) }}</td></tr>
        <tr class="h"><td>Factura</td><td>Fecha de Adquisición</td><td>Proveedor</td><td>Importe</td></tr>
        <tr class="val"><td>{{ $v($equipo->factura) }}</td><td style="text-align:center">{{ $equipo->fecha_adquisicion?->format('d/m/Y') }}</td><td>{{ $v($equipo->proveedor) }}</td><td>{{ $equipo->importe !== null ? '$'.number_format($equipo->importe, 2) : '' }}</td></tr>
    </table>
@endif

<div class="section">Datos de Asignación</div>
<table class="grid">
    <tr class="h"><td style="width: 52%">Adscripción</td><td>Usuario del Bien</td></tr>
    <tr class="val"><td style="padding: 4px">{{ $v($resguardo->adscripcion) }}</td><td style="padding: 4px">{{ $v($resguardo->persona->nombre) }}</td></tr>
</table>

<div class="section">Observaciones</div>
<div class="obs">{{ $v($resguardo->observaciones) }}</div>

<div class="legal">
    @if ($esVehiculo)
        El Vehículo descrito en este documento forma parte del Patrimonio de la CAPDAM y esta dedicado al servicio de la
        dependencia señalada, en la que el Custodio y Usuario es Responsable del buen uso que se haga de él.<br>
        El Vehículo descrito lo recibe en el estado de conservación señalado y asume la responsabilidad que se derive de su mal uso,
        pérdida o daños ocasionados por el mal trato o utilización ajena a las funciones Institucionales de la CAPDAM.
    @else
        El Bien descrito en este documento forma parte del Patrimonio de la CAPDAM y esta dedicado al servicio de la
        dependencia señalada, en la que el Custodio y Usuario es Responsable del buen uso que se haga de él.<br>
        El Bien descrito lo recibe en el estado de conservación señalado y asume la responsabilidad que se derive de su mal uso,
        pérdida o daños ocasionados por el mal trato o utilización ajena a las funciones Institucionales de la CAPDAM.
    @endif
</div>

@php
    // Same arrangement as the paper format: Vo.Bo. boxes on the left, Entrega/Recibe on the right.
    $filas = $esVehiculo
        ? [[RolFirma::VoBoDirectorAdministrativo, RolFirma::Entrega], [RolFirma::VoBoDirectorArea, RolFirma::Recibe]]
        : [[RolFirma::Entrega, RolFirma::Recibe]];
@endphp
<table class="firmas">
    @foreach ($filas as $fila)
        <tr>
            @foreach ($fila as $rol)
                @php $f = $firma($rol); @endphp
                <td>
                    <div class="enc">{{ $rol->encabezado() }}</div>
                    <div class="cargo">{{ $v($f?->cargo) }}</div>
                    <div class="linea">{{ $v($f?->nombre) }}</div>
                </td>
            @endforeach
        </tr>
    @endforeach
</table>

<div class="footer">
    <table>
        <tr>
            <td>{{ $nombreSistema ?? 'recapdam' }} — Control Patrimonial</td>
            <td style="text-align: right;">Fecha: {{ $generado->format('d/m/Y h:i:s a') }}</td>
        </tr>
    </table>
</div>

</body>
</html>
