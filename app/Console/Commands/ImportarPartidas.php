<?php

namespace App\Console\Commands;

use App\Support\ImportadorPartidas;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use InvalidArgumentException;

#[Signature('partidas:importar {archivo : Ruta del CSV del catálogo de partidas}')]
#[Description('Importa o actualiza el catálogo de partidas (productos) desde un CSV')]
class ImportarPartidas extends Command
{
    public function handle(ImportadorPartidas $importador): int
    {
        try {
            $r = $importador->importar($this->argument('archivo'));
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("Catálogo importado: {$r['total']} partidas ({$r['creadas']} nuevas, {$r['actualizadas']} actualizadas, {$r['desactivadas']} desactivadas).");

        return self::SUCCESS;
    }
}
