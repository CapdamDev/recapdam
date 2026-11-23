<?php

namespace App\Filament\Resources\Resguardos\Schemas;

use App\Models\Resguardo;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ResguardoInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Persona')
                    ->schema([
                        TextEntry::make('persona.nombre')->label('Nombre'),
                        TextEntry::make('persona.tipo')->label('Tipo')->badge(),
                        TextEntry::make('persona.numero_empleado')->label('No. empleado')->placeholder('—'),
                        TextEntry::make('persona.departamento')->label('Departamento / empresa')->placeholder('—'),
                    ])
                    ->columns(2),
                Section::make('Equipo')
                    ->schema([
                        TextEntry::make('equipo.numero_serie')->label('Número de serie')->copyable(),
                        TextEntry::make('equipo.partida.descripcion')->label('Producto')
                            ->placeholder(fn (Resguardo $record) => $record->equipo->categoria)
                            ->helperText(fn (Resguardo $record) => $record->equipo->partida?->codigo),
                        TextEntry::make('equipo.numero_inventario')->label('No. inventario')->placeholder('—'),
                        TextEntry::make('equipo.placa')->label('Placa')->placeholder('—')
                            ->visible(fn (Resguardo $record) => $record->equipo->esVehiculo()),
                        TextEntry::make('equipo.marca')->label('Marca')->placeholder('—'),
                        TextEntry::make('equipo.modelo')->label('Modelo')->placeholder('—'),
                    ])
                    ->columns(2),
                Section::make('Resguardo')
                    ->schema([
                        TextEntry::make('fecha_asignacion')->label('Fecha de asignación')->date('d/m/Y'),
                        TextEntry::make('adscripcion')->label('Adscripción')->placeholder('—'),
                        TextEntry::make('firmas')
                            ->state(fn (Resguardo $record) => $record->firmas->filter->estaFirmada()->count().' de '.$record->firmas->count())
                            ->badge()
                            ->color(fn (Resguardo $record) => $record->firmasCompletas() ? 'success' : 'warning'),
                        TextEntry::make('estado')
                            ->state(fn (Resguardo $record) => $record->estaActivo() ? 'Activo' : 'Devuelto el '.$record->fecha_devolucion->format('d/m/Y'))
                            ->badge()
                            ->color(fn (Resguardo $record) => $record->estaActivo() ? 'success' : 'gray'),
                        TextEntry::make('registradoPor.name')->label('Registrado por')->placeholder('—'),
                        TextEntry::make('created_at')->label('Registrado el')->dateTime('d/m/Y H:i'),
                        TextEntry::make('observaciones')->placeholder('—')->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}
