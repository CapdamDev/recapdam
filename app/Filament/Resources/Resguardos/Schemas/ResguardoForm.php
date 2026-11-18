<?php

namespace App\Filament\Resources\Resguardos\Schemas;

use App\Models\Equipo;
use App\Models\Persona;
use App\Models\Resguardo;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class ResguardoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('persona_id')
                    ->label('Persona')
                    ->relationship('persona', 'nombre')
                    ->searchable(['nombre', 'numero_empleado', 'departamento'])
                    ->preload()
                    ->required()
                    ->live()
                    // Suggest the person's department as the adscripción; staff can change it.
                    ->afterStateUpdated(function (?string $state, Get $get, Set $set) {
                        if ($state && blank($get('adscripcion'))) {
                            $set('adscripcion', Persona::find($state)?->departamento);
                        }
                    }),
                Select::make('equipo_id')
                    ->label('Equipo')
                    ->relationship('equipo', 'numero_serie')
                    ->getOptionLabelFromRecordUsing(fn (Equipo $record) => $record->etiqueta)
                    ->searchable(['numero_serie', 'numero_inventario', 'placa', 'categoria', 'marca', 'modelo'])
                    ->preload()
                    ->required()
                    ->live()
                    ->rule(fn (Get $get, ?Resguardo $record) => self::equipoDisponible($get, $record)),
                TextInput::make('adscripcion')
                    ->label('Adscripción')
                    ->placeholder('04-01- DESPACHO DIRECCION DE OPERACION')
                    ->columnSpanFull(),
                // Vehicles need a Vo.Bo. from the director of the receiving area.
                TextInput::make('director_area_cargo')
                    ->label('Cargo del director del área (Vo.Bo.)')
                    ->placeholder('DIRECTOR DE OPERACION')
                    ->visible(fn (Get $get) => self::esVehiculo($get))
                    ->required(fn (Get $get) => self::esVehiculo($get)),
                TextInput::make('director_area_nombre')
                    ->label('Nombre del director del área')
                    ->visible(fn (Get $get) => self::esVehiculo($get))
                    ->required(fn (Get $get) => self::esVehiculo($get)),
                DatePicker::make('fecha_asignacion')
                    ->label('Fecha de asignación')
                    ->default(now())
                    ->maxDate(now())
                    ->required(),
                DatePicker::make('fecha_devolucion')
                    ->label('Fecha de devolución')
                    ->helperText('Déjalo vacío mientras el equipo siga asignado.')
                    ->afterOrEqual('fecha_asignacion'),
                Textarea::make('observaciones')
                    ->columnSpanFull(),
            ]);
    }

    private static function esVehiculo(Get $get): bool
    {
        return (bool) Equipo::find($get('equipo_id'))?->esVehiculo();
    }

    /**
     * A device can only have one open resguardo at a time.
     */
    private static function equipoDisponible(Get $get, ?Resguardo $record): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($get, $record) {
            if (filled($get('fecha_devolucion'))) {
                return;
            }

            $abierto = Resguardo::query()
                ->with('persona')
                ->where('equipo_id', $value)
                ->whereNull('fecha_devolucion')
                ->when($record, fn ($query) => $query->whereKeyNot($record->getKey()))
                ->first();

            if ($abierto) {
                $fail("Este equipo ya está asignado a {$abierto->persona->nombre}. Registra su devolución primero.");
            }
        };
    }
}
