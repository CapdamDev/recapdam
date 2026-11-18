<?php

namespace App\Filament\Resources\Equipos\RelationManagers;

use App\Filament\Resources\Resguardos\ResguardoResource;
use App\Models\Resguardo;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Read-only assignment history. Resguardos are created and edited from ResguardoResource.
 */
class ResguardosRelationManager extends RelationManager
{
    protected static string $relationship = 'resguardos';

    protected static ?string $title = 'Historial de resguardos';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('persona.nombre')
                    ->label('Persona')
                    ->searchable(),
                TextColumn::make('fecha_asignacion')
                    ->label('Asignado')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('fecha_devolucion')
                    ->label('Devuelto')
                    ->date('d/m/Y')
                    ->placeholder('Activo')
                    ->sortable(),
                TextColumn::make('evidencias_count')
                    ->label('Evidencias')
                    ->counts('evidencias'),
            ])
            ->defaultSort('fecha_asignacion', 'desc')
            ->recordActions([
                ViewAction::make()
                    ->url(fn (Resguardo $record) => ResguardoResource::getUrl('view', ['record' => $record])),
            ]);
    }
}
