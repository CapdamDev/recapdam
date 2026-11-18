<?php

namespace App\Filament\Resources\Equipos\Schemas;

use App\Models\Equipo;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class EquipoForm
{
    public const ESTADOS_CONSERVACION = ['NUEVO', 'BUENO', 'REGULAR', 'MALO'];

    public static function configure(Schema $schema): Schema
    {
        $esVehiculo = fn (Get $get) => $get('categoria') === Equipo::CATEGORIA_VEHICULO;

        return $schema
            ->components([
                Section::make('Datos generales')
                    ->schema([
                        Select::make('categoria')
                            ->label('Categoría')
                            ->options(array_combine(Equipo::CATEGORIAS, Equipo::CATEGORIAS))
                            ->required()
                            ->live(),
                        TextInput::make('numero_serie')
                            ->label('Número de serie')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        TextInput::make('numero_inventario')
                            ->label('No. inventario')
                            ->unique(ignoreRecord: true),
                        TextInput::make('clasificacion')
                            ->label('Clasificación')
                            ->placeholder('05-04-01-01-01-00002'),
                        TextInput::make('clase')
                            ->placeholder('DIVERSOS'),
                        TextInput::make('subtipo')
                            ->label('Tipo')
                            ->placeholder('OTROS'),
                        TextInput::make('marca'),
                        TextInput::make('modelo')
                            ->label(fn (Get $get) => $esVehiculo($get) ? 'Línea' : 'Modelo'),
                        Select::make('estado_conservacion')
                            ->label('Estado de conservación')
                            ->options(array_combine(self::ESTADOS_CONSERVACION, self::ESTADOS_CONSERVACION)),
                        Textarea::make('descripcion')
                            ->label('Descripción')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Vehículo')
                    ->visible($esVehiculo)
                    ->schema([
                        TextInput::make('placa')
                            ->unique(ignoreRecord: true),
                        TextInput::make('numero_economico')
                            ->label('No. económico'),
                        TextInput::make('anio')
                            ->label('Modelo (año)')
                            ->numeric()
                            ->minValue(1950)
                            ->maxValue(now()->year + 1),
                        TextInput::make('cilindros')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(16),
                        TextInput::make('color'),
                        TextInput::make('capacidad')
                            ->placeholder('1480 KG'),
                        TextInput::make('combustible')
                            ->placeholder('GASOLINA'),
                        Textarea::make('equipo_instalado')
                            ->label('Equipo instalado')
                            ->placeholder('LLANTA DE REFACCION, HERRAMIENTAS AUXILIARES, GATO, TRIANGULO DE SEGURIDAD')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Adquisición')
                    ->schema([
                        TextInput::make('factura'),
                        DatePicker::make('fecha_adquisicion')
                            ->label('Fecha de adquisición'),
                        TextInput::make('proveedor'),
                        TextInput::make('importe')
                            ->numeric()
                            ->prefix('$')
                            ->minValue(0),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ])
            ->columns(1);
    }
}
