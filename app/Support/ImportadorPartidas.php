<?php

namespace App\Support;

use App\Models\Partida;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Imports CAPDAM's catalog of bienes (the "partidas" CSV exported from the patrimonio system).
 *
 * Re-importing is safe: rows are matched by code + description, changed rows are updated, and
 * rows missing from the new file are deactivated (never deleted, since equipos may reference them).
 */
class ImportadorPartidas
{
    private const SEGMENTOS = ['GRPO', 'SGRUPO', 'CLASE', 'SCLASE', 'TIPO', 'PRODUCTO'];

    private const CUENTA = ['GENERO', 'GRUPO', 'RUBRO', 'MAYOR', 'CTA', 'SCTA1', 'SCTA2', 'SCTA3', 'SCTA4', 'SCTA5'];

    /**
     * @return array{creadas: int, actualizadas: int, desactivadas: int, total: int}
     */
    public function importar(string $ruta): array
    {
        $filas = $this->unificarDuplicados($this->leer($ruta));

        if ($filas === []) {
            throw new InvalidArgumentException('El archivo no contiene partidas.');
        }

        $this->calcularJerarquia($filas);

        return DB::transaction(function () use ($filas) {
            $existentes = Partida::query()->get()->keyBy(fn (Partida $p) => $this->llave($p->codigo, $p->descripcion));
            $vistas = [];
            $creadas = $actualizadas = 0;

            foreach ($filas as $fila) {
                $llave = $this->llave($fila['codigo'], $fila['descripcion']);
                $vistas[$llave] = true;

                $partida = $existentes->get($llave) ?? new Partida;
                $partida->fill([...$fila, 'activo' => true]);

                if (! $partida->exists) {
                    $creadas++;
                } elseif ($partida->isDirty()) {
                    $actualizadas++;
                }

                $partida->save();
            }

            $desactivar = $existentes->reject(fn (Partida $p, string $llave) => isset($vistas[$llave]) || ! $p->activo);
            Partida::query()->whereKey($desactivar->modelKeys())->update(['activo' => false]);

            return [
                'creadas' => $creadas,
                'actualizadas' => $actualizadas,
                'desactivadas' => $desactivar->count(),
                'total' => count($vistas),
            ];
        });
    }

    /**
     * The same code + description can appear twice (e.g. "TABLETAS"); keep one row, filling
     * empty fields such as the account from the other.
     *
     * @param list<array<string, mixed>> $filas
     * @return list<array<string, mixed>>
     */
    private function unificarDuplicados(array $filas): array
    {
        $unicas = [];

        foreach ($filas as $fila) {
            $llave = $this->llave($fila['codigo'], $fila['descripcion']);

            if (! isset($unicas[$llave])) {
                $unicas[$llave] = $fila;

                continue;
            }

            foreach (['cuenta_contable', 'nota'] as $campo) {
                $unicas[$llave][$campo] ??= $fila[$campo];
            }
        }

        return array_values($unicas);
    }

    /** @return list<array<string, mixed>> */
    private function leer(string $ruta): array
    {
        $contenido = @file_get_contents($ruta);

        if ($contenido === false) {
            throw new InvalidArgumentException("No se pudo leer el archivo {$ruta}.");
        }

        $contenido = preg_replace('/^\xEF\xBB\xBF/', '', $contenido); // UTF-8 BOM
        if (! mb_check_encoding($contenido, 'UTF-8')) {
            $contenido = mb_convert_encoding($contenido, 'UTF-8', 'Windows-1252'); // Excel "CSV" export
        }

        $lineas = array_map('str_getcsv', preg_split('/\r\n|\n|\r/', trim($contenido)));
        $encabezado = array_map(fn ($c) => mb_strtoupper(trim((string) $c)), array_shift($lineas) ?? []);
        $indice = array_flip($encabezado);

        $faltantes = array_diff([...self::SEGMENTOS, 'DESCRIPCION DEL PRODUCTO'], $encabezado);
        if ($faltantes !== []) {
            throw new InvalidArgumentException('Faltan columnas en el archivo: '.implode(', ', $faltantes).'.');
        }

        $valor = fn (array $linea, string $columna) => isset($indice[$columna]) ? trim((string) ($linea[$indice[$columna]] ?? '')) : '';
        $filas = [];

        foreach ($lineas as $numero => $linea) {
            $segmentos = array_map(fn ($c) => $valor($linea, $c), self::SEGMENTOS);
            $descripcion = preg_replace('/\s+/', ' ', $valor($linea, 'DESCRIPCION DEL PRODUCTO'));

            if ($segmentos[0] === '' || $descripcion === '') {
                continue; // separator or empty row
            }

            if (array_filter($segmentos, fn ($s) => ! ctype_digit($s) || (int) $s > 255) !== []) {
                throw new InvalidArgumentException('Código inválido en la fila '.($numero + 2).': '.implode('-', $segmentos).'.');
            }

            $segmentos = array_map('intval', $segmentos);
            $nota = $valor($linea, 'DESCRIPCION CUENTA');

            $filas[] = [
                'codigo' => implode('-', $segmentos),
                'grupo' => $segmentos[0],
                'subgrupo' => $segmentos[1],
                'clase' => $segmentos[2],
                'subclase' => $segmentos[3],
                'tipo' => $segmentos[4],
                'producto' => $segmentos[5],
                'nivel' => self::nivel($segmentos),
                'descripcion' => $descripcion,
                'cuenta_contable' => implode('.', array_filter(array_map(fn ($c) => $valor($linea, $c), self::CUENTA), fn ($s) => $s !== '')) ?: null,
                'nota' => $nota !== '' ? $nota : null,
            ];
        }

        return $filas;
    }

    /**
     * Fills "ruta" (ancestor descriptions, from subgrupo down) and "seleccionable" (no descendants).
     *
     * @param list<array<string, mixed>> $filas
     */
    private function calcularJerarquia(array &$filas): void
    {
        $segmentos = fn (array $f) => [$f['grupo'], $f['subgrupo'], $f['clase'], $f['subclase'], $f['tipo'], $f['producto']];

        $nombrePorCodigo = [];
        $conHijos = [];

        foreach ($filas as $fila) {
            $nombrePorCodigo[$fila['codigo']] ??= $fila['descripcion'];

            // Mark every ancestor prefix as having descendants.
            $s = $segmentos($fila);
            for ($nivel = 1; $nivel < $fila['nivel']; $nivel++) {
                $conHijos[self::codigoAncestro($s, $nivel)] = true;
            }
        }

        foreach ($filas as &$fila) {
            $s = $segmentos($fila);
            $ruta = [];

            // Skip level 1 ("BIENES MUEBLES, INMUEBLES E INTANGIBLES"), which every row shares.
            for ($nivel = 2; $nivel < $fila['nivel']; $nivel++) {
                if ($nombre = $nombrePorCodigo[self::codigoAncestro($s, $nivel)] ?? null) {
                    $ruta[] = $nombre;
                }
            }

            $fila['ruta'] = $ruta ? implode(' › ', $ruta) : null;
            $fila['seleccionable'] = ! isset($conHijos[$fila['codigo']]);
        }
    }

    /** @param list<int> $segmentos */
    private static function nivel(array $segmentos): int
    {
        for ($i = 5; $i >= 0; $i--) {
            if ($segmentos[$i] !== 0) {
                return $i + 1;
            }
        }

        return 0;
    }

    /** @param list<int> $segmentos */
    private static function codigoAncestro(array $segmentos, int $nivel): string
    {
        return implode('-', [...array_slice($segmentos, 0, $nivel), ...array_fill(0, 6 - $nivel, 0)]);
    }

    private function llave(string $codigo, string $descripcion): string
    {
        return $codigo.'|'.mb_strtoupper($descripcion);
    }
}
