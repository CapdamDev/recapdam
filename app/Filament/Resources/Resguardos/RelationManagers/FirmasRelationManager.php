<?php

namespace App\Filament\Resources\Resguardos\RelationManagers;

use App\Models\Firma;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Signatures are collected on the printed form; staff record here which ones are done.
 * Rows are created by Resguardo::sincronizarFirmas(), never by hand.
 */
class FirmasRelationManager extends RelationManager
{
    protected static string $relationship = 'firmas';

    protected static ?string $title = 'Firmas';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->paginated(false)
            ->modifyQueryUsing(fn ($query) => $query->orderByRaw("FIELD(rol, 'vobo_director_administrativo', 'entrega', 'vobo_director_area', 'recibe')"))
            ->columns([
                TextColumn::make('rol')
                    ->label('Firma'),
                TextColumn::make('nombre')
                    ->placeholder('Sin asignar')
                    ->description(fn (Firma $record) => $record->cargo),
                TextColumn::make('firmado_en')
                    ->label('Estado')
                    ->date('d/m/Y')
                    ->badge()
                    ->color(fn (Firma $record) => $record->estaFirmada() ? 'success' : 'warning')
                    ->formatStateUsing(fn (Firma $record) => 'Firmado el '.$record->firmado_en->format('d/m/Y'))
                    ->placeholder('Pendiente'),
                TextColumn::make('marcadoPor.name')
                    ->label('Registrado por')
                    ->placeholder('—'),
            ])
            ->recordActions([
                Action::make('marcarFirmada')
                    ->label('Marcar firmada')
                    ->icon(Heroicon::OutlinedCheck)
                    ->color('success')
                    ->visible(fn (Firma $record) => ! $record->estaFirmada())
                    ->schema([
                        DatePicker::make('firmado_en')
                            ->label('Fecha de firma')
                            ->default(now())
                            ->maxDate(now())
                            ->required(),
                    ])
                    ->action(fn (Firma $record, array $data) => $record->update([
                        'firmado_en' => $data['firmado_en'],
                        'marcado_por' => auth()->id(),
                    ])),
                Action::make('desmarcar')
                    ->label('Desmarcar')
                    ->icon(Heroicon::OutlinedXMark)
                    ->color('gray')
                    ->requiresConfirmation()
                    // Undoing a recorded signature is a correction only admins may make.
                    ->visible(fn (Firma $record) => $record->estaFirmada() && auth()->user()->esAdmin())
                    ->action(function (Firma $record) {
                        $record->update(['firmado_en' => null, 'marcado_por' => null]);
                        // Refresh name/position now that it's unsigned again.
                        $record->resguardo->sincronizarFirmas();
                    }),
            ]);
    }
}
