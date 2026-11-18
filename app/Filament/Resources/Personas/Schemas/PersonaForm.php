<?php

namespace App\Filament\Resources\Personas\Schemas;

use App\Enums\TipoPersona;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class PersonaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nombre')
                    ->label('Nombre completo')
                    ->required()
                    ->maxLength(255),
                Select::make('tipo')
                    ->options(TipoPersona::class)
                    ->default(TipoPersona::Empleado)
                    ->required()
                    ->live(),
                TextInput::make('numero_empleado')
                    ->label('Número de empleado')
                    ->unique(ignoreRecord: true)
                    ->visible(fn (Get $get) => $get('tipo') === TipoPersona::Empleado || $get('tipo') === TipoPersona::Empleado->value),
                TextInput::make('departamento')
                    ->label('Departamento / empresa'),
                TextInput::make('email')
                    ->label('Correo electrónico')
                    ->email(),
                TextInput::make('telefono')
                    ->label('Teléfono')
                    ->tel(),
            ]);
    }
}
