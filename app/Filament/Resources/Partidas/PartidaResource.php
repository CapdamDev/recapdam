<?php

namespace App\Filament\Resources\Partidas;

use App\Filament\Resources\Partidas\Pages\ListPartidas;
use App\Models\Partida;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Browse and search the catalog of bienes. Read-only; admins replace it with "Importar catálogo".
 */
class PartidaResource extends Resource
{
    protected static ?string $model = Partida::class;

    protected static ?string $modelLabel = 'partida';

    protected static ?string $navigationLabel = 'Catálogo de partidas';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?int $navigationSort = 50;

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort(fn (Builder $query) => $query->orderBy('grupo')->orderBy('subgrupo')->orderBy('clase')
                ->orderBy('subclase')->orderBy('tipo')->orderBy('producto'))
            ->searchPlaceholder('Buscar producto o código…')
            ->columns([
                TextColumn::make('descripcion')
                    ->label('Producto')
                    ->description(fn (Partida $record) => $record->ruta)
                    ->weight(fn (Partida $record) => $record->seleccionable ? 'semibold' : null)
                    ->searchable(query: fn (Builder $query, string $search) => $query->buscar($search))
                    ->wrap(),
                TextColumn::make('codigo')
                    ->label('Código')
                    ->fontFamily('mono')
                    ->copyable(),
                TextColumn::make('cuenta_contable')
                    ->label('Cuenta contable')
                    ->fontFamily('mono')
                    ->placeholder('—'),
                TextColumn::make('nota')
                    ->badge()
                    ->color('warning')
                    ->placeholder('—')
                    ->toggleable(),
                IconColumn::make('seleccionable')
                    ->label('Asignable')
                    ->tooltip('Solo los productos finales (sin subniveles) se pueden asignar a un equipo.')
                    ->boolean(),
                TextColumn::make('equipos_count')
                    ->label('Equipos')
                    ->counts('equipos')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('subgrupo')
                    ->label('Grupo')
                    ->options(fn () => Partida::query()->where('nivel', 2)->orderBy('subgrupo')->pluck('descripcion', 'subgrupo')->all()),
                TernaryFilter::make('seleccionable')
                    ->label('Tipo')
                    ->trueLabel('Solo productos asignables')
                    ->falseLabel('Solo niveles de agrupación')
                    ->default(true),
                TernaryFilter::make('activo')
                    ->label('Vigencia')
                    ->trueLabel('Vigentes')
                    ->falseLabel('Ya no están en el catálogo importado')
                    ->default(true),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPartidas::route('/'),
        ];
    }
}
