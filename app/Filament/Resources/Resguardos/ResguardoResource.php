<?php

namespace App\Filament\Resources\Resguardos;

use App\Filament\Resources\Resguardos\Pages\CreateResguardo;
use App\Filament\Resources\Resguardos\Pages\EditResguardo;
use App\Filament\Resources\Resguardos\Pages\ListResguardos;
use App\Filament\Resources\Resguardos\Pages\ViewResguardo;
use App\Filament\Resources\Resguardos\RelationManagers\EvidenciasRelationManager;
use App\Filament\Resources\Resguardos\RelationManagers\FirmasRelationManager;
use App\Filament\Resources\Resguardos\Schemas\ResguardoForm;
use App\Filament\Resources\Resguardos\Schemas\ResguardoInfolist;
use App\Filament\Resources\Resguardos\Tables\ResguardosTable;
use App\Models\Resguardo;
use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ResguardoResource extends Resource
{
    protected static ?string $model = Resguardo::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    public static function getRecordTitle(?Model $record): string
    {
        return $record ? "{$record->persona->nombre} — {$record->equipo->numero_serie}" : 'Resguardo';
    }

    public static function form(Schema $schema): Schema
    {
        return ResguardoForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ResguardoInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ResguardosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            FirmasRelationManager::class,
            EvidenciasRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListResguardos::route('/'),
            'create' => CreateResguardo::route('/create'),
            'view' => ViewResguardo::route('/{record}'),
            'edit' => EditResguardo::route('/{record}/edit'),
        ];
    }
}
