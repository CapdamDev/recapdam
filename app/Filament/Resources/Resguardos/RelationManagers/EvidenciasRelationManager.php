<?php

namespace App\Filament\Resources\Resguardos\RelationManagers;

use App\Enums\TipoEvidencia;
use App\Models\Evidencia;
use App\Models\Resguardo;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class EvidenciasRelationManager extends RelationManager
{
    protected static string $relationship = 'evidencias';

    // Show (and allow adding) evidencias on the view page, not only on edit.
    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        /** @var Resguardo $resguardo */
        $resguardo = $this->getOwnerRecord();

        return $schema
            ->components([
                Select::make('tipo')
                    ->options(TipoEvidencia::class)
                    ->default(TipoEvidencia::FotoSerie)
                    ->required()
                    ->columnSpanFull(),
                FileUpload::make('archivo')
                    ->label('Foto o documento')
                    ->helperText('Foto de la etiqueta con el número de serie, resguardo firmado, etc. Imagen o PDF, máx. 10 MB.')
                    ->disk('local')
                    ->directory('evidencias')
                    ->visibility('private')
                    ->acceptedFileTypes(['image/*', 'application/pdf'])
                    ->maxSize(10 * 1024)
                    ->storeFileNamesIn('archivo_nombre_original')
                    ->openable()
                    ->downloadable()
                    ->required()
                    ->columnSpanFull(),
                // Prefilled from the resguardo; staff confirm or correct what the evidence actually shows.
                TextInput::make('numero_serie')
                    ->label('Número de serie')
                    ->default($resguardo->equipo->numero_serie)
                    ->required(),
                TextInput::make('nombre_persona')
                    ->label('Nombre de la persona')
                    ->default($resguardo->persona->nombre)
                    ->required(),
                Textarea::make('descripcion')
                    ->label('Descripción')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        /** @var Resguardo $resguardo */
        $resguardo = $this->getOwnerRecord();

        return $table
            ->recordTitleAttribute('archivo_nombre_original')
            ->columns([
                TextColumn::make('tipo')
                    ->badge(),
                TextColumn::make('archivo_nombre_original')
                    ->label('Archivo')
                    ->icon(Heroicon::OutlinedPaperClip)
                    ->url(fn (Evidencia $record) => Storage::disk('local')->temporaryUrl($record->archivo, now()->addMinutes(30)))
                    ->openUrlInNewTab(),
                TextColumn::make('numero_serie')
                    ->label('Número de serie')
                    ->searchable(),
                TextColumn::make('nombre_persona')
                    ->label('Persona')
                    ->searchable(),
                IconColumn::make('coincide')
                    ->label('Coincide')
                    ->tooltip('¿El número de serie y el nombre coinciden con el resguardo?')
                    ->state(fn (Evidencia $record) => self::normalizar($record->numero_serie) === self::normalizar($resguardo->equipo->numero_serie)
                        && self::normalizar($record->nombre_persona) === self::normalizar($resguardo->persona->nombre))
                    ->boolean(),
                TextColumn::make('subidoPor.name')
                    ->label('Subido por')
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                CreateAction::make()->label('Subir evidencia'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    private static function normalizar(?string $valor): string
    {
        return mb_strtolower(preg_replace('/\s+/', ' ', trim((string) $valor)));
    }
}
