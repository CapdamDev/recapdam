<?php

namespace App\Filament\Resources\Equipos\Tables;

use App\Models\Equipo;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EquiposTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['resguardoActivo.persona', 'partida']))
            ->columns([
                TextColumn::make('numero_serie')
                    ->label('Número de serie')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('numero_inventario')
                    ->label('No. inventario')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('partida.descripcion')
                    ->label('Producto')
                    ->description(fn (Equipo $record) => $record->partida?->codigo)
                    ->placeholder(fn (Equipo $record) => $record->categoria)
                    ->searchable()
                    ->wrap(),
                TextColumn::make('categoria')
                    ->label('Grupo')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('placa')
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('marca')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('modelo')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('resguardoActivo.persona.nombre')
                    ->label('Asignado a')
                    ->placeholder('Disponible'),
            ])
            ->defaultSort('numero_serie')
            ->filters([
                SelectFilter::make('categoria')
                    ->label('Grupo')
                    ->options(fn () => Equipo::query()->whereNotNull('categoria')->distinct()->orderBy('categoria')->pluck('categoria', 'categoria')->all()),
                TernaryFilter::make('asignado')
                    ->queries(
                        true: fn (Builder $query) => $query->whereHas('resguardos', fn ($q) => $q->whereNull('fecha_devolucion')),
                        false: fn (Builder $query) => $query->whereDoesntHave('resguardos', fn ($q) => $q->whereNull('fecha_devolucion')),
                    ),
            ])
            ->recordActions([
                EditAction::make(),
                // Devices with resguardos can't be deleted (FK restrict) so their history is preserved.
                DeleteAction::make()
                    ->hidden(fn (Equipo $record) => $record->resguardos()->exists()),
            ]);
    }
}
