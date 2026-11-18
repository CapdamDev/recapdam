<?php

namespace App\Filament\Resources\Personas\Tables;

use App\Enums\TipoPersona;
use App\Models\Persona;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PersonasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tipo')
                    ->badge(),
                TextColumn::make('numero_empleado')
                    ->label('No. empleado')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('departamento')
                    ->label('Departamento / empresa')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('resguardos_activos_count')
                    ->label('Equipos asignados')
                    ->counts(['resguardos as resguardos_activos_count' => fn ($query) => $query->whereNull('fecha_devolucion')])
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Correo')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('telefono')
                    ->label('Teléfono')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('nombre')
            ->filters([
                SelectFilter::make('tipo')
                    ->options(TipoPersona::class),
            ])
            ->recordActions([
                EditAction::make(),
                // People with resguardos can't be deleted (FK restrict) so their history is preserved.
                DeleteAction::make()
                    ->hidden(fn (Persona $record) => $record->resguardos()->exists()),
            ]);
    }
}
