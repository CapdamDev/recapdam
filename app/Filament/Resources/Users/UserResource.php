<?php

namespace App\Filament\Resources\Users;

use App\Enums\RolUsuario;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Admin-only (see UserPolicy). Accounts are deactivated rather than deleted.
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $modelLabel = 'usuario';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        // An admin can't lock themselves out by changing their own role or deactivating themselves.
        $esUnoMismo = fn (?User $record) => $record?->is(auth()->user()) ?? false;

        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Correo electrónico')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true),
                Radio::make('rol')
                    ->options(RolUsuario::class)
                    ->default(RolUsuario::Patrimonio)
                    ->required()
                    ->disabled($esUnoMismo)
                    ->dehydrated(fn (?User $record) => ! $esUnoMismo($record)),
                Toggle::make('activo')
                    ->helperText('Una cuenta inactiva no puede iniciar sesión. Su historial se conserva.')
                    ->default(true)
                    ->disabled($esUnoMismo)
                    ->dehydrated(fn (?User $record) => ! $esUnoMismo($record)),
                TextInput::make('password')
                    ->label(fn (string $operation) => $operation === 'create' ? 'Contraseña' : 'Nueva contraseña')
                    ->helperText(fn (string $operation) => $operation === 'create' ? null : 'Déjala vacía para conservar la actual.')
                    ->password()
                    ->revealable()
                    ->minLength(8)
                    ->required(fn (string $operation) => $operation === 'create')
                    ->dehydrated(fn (?string $state) => filled($state))
                    ->confirmed(),
                TextInput::make('password_confirmation')
                    ->label('Confirmar contraseña')
                    ->password()
                    ->revealable()
                    ->requiredWith('password')
                    ->dehydrated(false),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Correo')
                    ->searchable(),
                TextColumn::make('rol')
                    ->badge(),
                IconColumn::make('activo')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Alta')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('rol')
                    ->options(RolUsuario::class),
                TernaryFilter::make('activo')
                    ->default(true),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('alternarActivo')
                    ->label(fn (User $record) => $record->activo ? 'Desactivar' : 'Reactivar')
                    ->icon(fn (User $record) => $record->activo ? Heroicon::OutlinedLockClosed : Heroicon::OutlinedLockOpen)
                    ->color(fn (User $record) => $record->activo ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->hidden(fn (User $record) => $record->is(auth()->user()))
                    ->action(fn (User $record) => $record->update(['activo' => ! $record->activo])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }
}
