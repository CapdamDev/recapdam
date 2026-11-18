<?php

namespace App\Filament\Resources\Resguardos\Actions;

use App\Models\Resguardo;
use App\Support\ResguardoPdf;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class DescargarPdfAction
{
    public static function make(): Action
    {
        return Action::make('descargarPdf')
            ->label('Descargar PDF')
            ->icon(Heroicon::OutlinedDocumentArrowDown)
            ->color('gray')
            ->action(function (Resguardo $record) {
                $pdf = ResguardoPdf::generar($record);

                return response()->streamDownload(
                    function () use ($pdf) {
                        echo $pdf;
                    },
                    ResguardoPdf::nombreArchivo($record),
                    ['Content-Type' => 'application/pdf'],
                );
            });
    }
}
