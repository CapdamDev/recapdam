<?php

namespace App\Filament\Resources\Partidas\Pages;

use App\Filament\Resources\Partidas\PartidaResource;
use App\Models\Partida;
use App\Support\ImportadorPartidas;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class ListPartidas extends ListRecords
{
    protected static string $resource = PartidaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('importar')
                ->label('Importar catálogo')
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->authorize('importar', Partida::class)
                ->modalDescription('Sube el CSV del catálogo de partidas. Las partidas nuevas se agregan, las existentes se actualizan y las que ya no aparezcan se marcan como no vigentes (los equipos que las usan no cambian).')
                ->schema([
                    FileUpload::make('archivo')
                        ->label('Archivo CSV')
                        ->disk('local')
                        ->directory('importaciones')
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
                        ->maxSize(5 * 1024)
                        ->required(),
                ])
                ->action(function (array $data, ImportadorPartidas $importador) {
                    $ruta = Storage::disk('local')->path($data['archivo']);

                    try {
                        $r = $importador->importar($ruta);
                    } catch (InvalidArgumentException $e) {
                        Notification::make()->danger()->title('No se pudo importar')->body($e->getMessage())->send();

                        return;
                    } finally {
                        Storage::disk('local')->delete($data['archivo']);
                    }

                    Notification::make()
                        ->success()
                        ->title('Catálogo importado')
                        ->body("{$r['total']} partidas: {$r['creadas']} nuevas, {$r['actualizadas']} actualizadas, {$r['desactivadas']} ya no vigentes.")
                        ->send();
                }),
        ];
    }
}
