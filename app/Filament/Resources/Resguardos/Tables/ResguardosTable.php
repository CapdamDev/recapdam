<?php

namespace App\Filament\Resources\Resguardos\Tables;

use App\Filament\Resources\Resguardos\Actions\DescargarPdfAction;
use App\Filament\Resources\Resguardos\Actions\RegistrarDevolucionAction;
use App\Models\Resguardo;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ResguardosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['persona', 'equipo', 'firmas']))
            ->columns([
                TextColumn::make('persona.nombre')
                    ->label('Persona')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('equipo.numero_serie')
                    ->label('Número de serie')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('equipo.categoria')
                    ->label('Equipo')
                    ->description(fn (Resguardo $record) => trim("{$record->equipo->marca} {$record->equipo->modelo}")),
                TextColumn::make('fecha_asignacion')
                    ->label('Asignado')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('estado')
                    ->state(fn (Resguardo $record) => $record->estaActivo() ? 'Activo' : 'Devuelto')
                    ->badge()
                    ->color(fn (string $state) => $state === 'Activo' ? 'success' : 'gray')
                    ->description(fn (Resguardo $record) => $record->fecha_devolucion?->format('d/m/Y')),
                TextColumn::make('firmas_estado')
                    ->label('Firmas')
                    ->state(fn (Resguardo $record) => $record->firmas->filter->estaFirmada()->count().'/'.$record->firmas->count())
                    ->badge()
                    ->color(fn (Resguardo $record) => $record->firmasCompletas() ? 'success' : 'warning'),
                TextColumn::make('evidencias_count')
                    ->label('Evidencias')
                    ->counts('evidencias')
                    ->badge()
                    ->color(fn (int $state) => $state === 0 ? 'danger' : 'gray'),
            ])
            ->defaultSort('fecha_asignacion', 'desc')
            ->filters([
                TernaryFilter::make('activo')
                    ->label('Estado')
                    ->trueLabel('Activos')
                    ->falseLabel('Devueltos')
                    ->default(true)
                    ->queries(
                        true: fn (Builder $query) => $query->whereNull('fecha_devolucion'),
                        false: fn (Builder $query) => $query->whereNotNull('fecha_devolucion'),
                    ),
                TernaryFilter::make('firmas_pendientes')
                    ->label('Firmas')
                    ->trueLabel('Pendientes de firma')
                    ->falseLabel('Firmados')
                    ->queries(
                        true: fn (Builder $query) => $query->whereHas('firmas', fn ($q) => $q->whereNull('firmado_en')),
                        false: fn (Builder $query) => $query->whereDoesntHave('firmas', fn ($q) => $q->whereNull('firmado_en')),
                    ),
                TernaryFilter::make('sin_evidencias')
                    ->label('Evidencias')
                    ->trueLabel('Sin evidencias')
                    ->falseLabel('Con evidencias')
                    ->queries(
                        true: fn (Builder $query) => $query->doesntHave('evidencias'),
                        false: fn (Builder $query) => $query->has('evidencias'),
                    ),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                RegistrarDevolucionAction::make(),
                DescargarPdfAction::make(),
            ]);
    }
}
