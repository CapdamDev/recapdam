<?php

namespace App\Filament\Resources\Resguardos\Actions;

use App\Models\Resguardo;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Support\Icons\Heroicon;

class RegistrarDevolucionAction
{
    public static function make(): Action
    {
        return Action::make('registrarDevolucion')
            ->label('Registrar devolución')
            ->icon(Heroicon::OutlinedArrowUturnLeft)
            ->color('warning')
            ->visible(fn (Resguardo $record) => $record->estaActivo())
            ->schema(fn (Resguardo $record) => [
                DatePicker::make('fecha_devolucion')
                    ->label('Fecha de devolución')
                    ->default(now())
                    ->minDate($record->fecha_asignacion)
                    ->maxDate(now())
                    ->required(),
                Textarea::make('observaciones')
                    ->label('Observaciones')
                    ->default($record->observaciones),
            ])
            ->action(function (Resguardo $record, array $data) {
                $record->update($data);
            })
            ->successNotificationTitle('Devolución registrada');
    }
}
