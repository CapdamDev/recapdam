<?php

namespace App\Filament\Resources\Equipos\Schemas;

use App\Models\Equipo;
use App\Models\Partida;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class EquipoForm
{
    public const ESTADOS_CONSERVACION = ['NUEVO', 'BUENO', 'REGULAR', 'MALO'];

    /**
     * Product search over the catalog of partidas: any words in any order, or a code prefix like "5-1-1".
     */
    public static function productoField(): Select
    {
        return Select::make('partida_id')
            ->label('Producto (catálogo de partidas)')
            ->searchable()
            ->allowHtml()
            ->searchDebounce(300)
            ->searchPrompt('Escribe el nombre del producto o su código, p. ej. «silla secretarial» o «5-1-1»')
            ->searchingMessage('Buscando en el catálogo…')
            ->noSearchResultsMessage('Sin resultados. Prueba con menos palabras, otra palabra o el código.')
            ->getSearchResultsUsing(fn (string $search) => Partida::query()
                ->seleccionables()
                ->buscar($search)
                ->orderBy('descripcion')
                ->limit(50)
                ->get()
                ->mapWithKeys(fn (Partida $partida) => [$partida->getKey() => $partida->etiquetaHtml()])
                ->all())
            ->getOptionLabelUsing(fn ($value) => Partida::find($value)?->etiquetaHtml())
            ->required()
            ->live()
            ->rule(fn (?Equipo $record) => function (string $attribute, mixed $value, Closure $fail) use ($record) {
                // Leaves only; an inactive partida is accepted only if the equipo already had it.
                $valida = Partida::query()
                    ->whereKey($value)
                    ->where('seleccionable', true)
                    ->where(fn ($q) => $q->where('activo', true)->when($record?->partida_id, fn ($q, $actual) => $q->orWhere('id', $actual)))
                    ->exists();

                if (! $valida) {
                    $fail('Elige un producto del catálogo.');
                }
            })
            ->columnSpanFull();
    }

    public static function configure(Schema $schema): Schema
    {
        // Vehicle fields depend on the chosen product (group 5-4); equipos from before the catalog fall back to their categoría.
        $esVehiculo = fn (Get $get, ?Equipo $record) => filled($get('partida_id'))
            ? (bool) Partida::find($get('partida_id'))?->esVehiculo()
            : (bool) $record?->esVehiculo();

        return $schema
            ->components([
                Section::make('Datos generales')
                    ->schema([
                        self::productoField(),
                        TextEntry::make('producto_detalle')
                            ->hiddenLabel()
                            ->visible(fn (Get $get) => filled($get('partida_id')))
                            ->state(function (Get $get) {
                                $partida = Partida::find($get('partida_id'));

                                return $partida
                                    ? 'Grupo: '.$partida->nombreGrupo().' · Cuenta contable: '.($partida->cuenta_contable ?? 'sin cuenta en el catálogo')
                                    : null;
                            })
                            ->color('gray')
                            ->columnSpanFull(),
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
                            ->label(fn (Get $get, ?Equipo $record) => $esVehiculo($get, $record) ? 'Línea' : 'Modelo'),
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
