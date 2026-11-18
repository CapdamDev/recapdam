<?php

namespace App\Filament\Resources\Firmantes;

use App\Enums\RolFirma;
use App\Filament\Resources\Firmantes\Pages\ManageFirmantes;
use App\Models\Firmante;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Who signs the fixed boxes (Vo.Bo. Director Administrativo, Entrega) on every printed resguardo.
 */
class FirmanteResource extends Resource
{
    protected static ?string $model = Firmante::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPencilSquare;

    protected static string|UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?string $recordTitleAttribute = 'nombre';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('rol')
                    ->label('Firma')
                    ->options(collect(RolFirma::fijos())->mapWithKeys(fn (RolFirma $rol) => [$rol->value => $rol->getLabel()]))
                    ->unique(ignoreRecord: true)
                    ->required(),
                TextInput::make('nombre')
                    ->label('Nombre como aparece en el formato')
                    ->placeholder('M.F. NOMBRE APELLIDO APELLIDO')
                    ->required(),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->description('Los cambios se aplican a las firmas que aún están pendientes; las ya firmadas no cambian.')
            ->columns([
                TextColumn::make('rol')->label('Firma'),
                TextColumn::make('nombre'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageFirmantes::route('/'),
        ];
    }
}
