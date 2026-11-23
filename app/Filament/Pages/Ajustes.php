<?php

namespace App\Filament\Pages;

use App\Models\Ajuste;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Branding settings (Root Admin only): system name, logo, favicon and color palette.
 *
 * @property-read Schema $form
 */
class Ajustes extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSwatch;

    protected static string|UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?int $navigationSort = 100;

    protected static ?string $title = 'Ajustes';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->esAdmin();
    }

    public function mount(): void
    {
        $this->form->fill(Ajuste::query()->first()?->attributesToArray() ?? Ajuste::porDefecto()->attributesToArray());
    }

    public function form(Schema $schema): Schema
    {
        $color = fn (string $campo, string $etiqueta) => ColorPicker::make($campo)
            ->label($etiqueta)
            ->hex()
            ->regex('/^#[0-9a-fA-F]{6}$/');

        return $schema
            ->statePath('data')
            ->components([
                Section::make('Sistema')
                    ->description('Nombre que aparece en el panel, la pestaña del navegador y el pie de los PDF.')
                    ->aside()
                    ->schema([
                        TextInput::make('nombre_sistema')
                            ->label('Nombre del sistema')
                            ->required()
                            ->maxLength(60),
                    ]),
                Section::make('Logo y favicon')
                    ->description('El logo se usa en el panel, la pantalla de inicio de sesión y los resguardos PDF. Recomendado: PNG con fondo transparente.')
                    ->aside()
                    ->schema([
                        FileUpload::make('logo')
                            ->image()
                            ->acceptedFileTypes(['image/png', 'image/jpeg'])
                            ->disk('public')
                            ->directory('ajustes')
                            ->visibility('public')
                            ->maxSize(2048)
                            ->helperText('Vacío = se muestra el nombre del sistema en el panel y el logotipo original en los PDF (public/img/logo-capdam.png, si existe). PNG o JPG; el PDF no admite SVG.'),
                        FileUpload::make('favicon')
                            ->image()
                            ->acceptedFileTypes(['image/png', 'image/x-icon', 'image/vnd.microsoft.icon', 'image/svg+xml'])
                            ->disk('public')
                            ->directory('ajustes')
                            ->visibility('public')
                            ->maxSize(512)
                            ->helperText('Icono de la pestaña del navegador. PNG cuadrado de 32×32 o 64×64 px, ICO o SVG.'),
                    ]),
                Section::make('Paleta de colores')
                    ->description('Se aplica a todo el sistema. Los colores «claro» y «oscuro» pueden dejarse vacíos para calcularlos del principal.')
                    ->aside()
                    ->schema([
                        $color('color_principal', 'Color principal')
                            ->required()
                            ->helperText('Encabezados, botones y enlaces. Debe leerse bien con texto blanco encima.'),
                        $color('color_acento', 'Color de acento')
                            ->required()
                            ->helperText('Líneas decorativas, como la de la barra superior.'),
                        $color('color_principal_claro', 'Color principal claro')
                            ->helperText('Bordes y detalles. Vacío = calcular del principal.'),
                        $color('color_principal_oscuro', 'Color principal oscuro')
                            ->helperText('Fondos oscuros y estados activos. Vacío = calcular del principal.'),
                        $color('color_texto', 'Color del texto')
                            ->required()
                            ->helperText('Títulos y texto destacado.'),
                    ])
                    ->columns(2),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')
                                ->label('Guardar')
                                ->submit('save')
                                ->keyBindings(['mod+s']),
                        ]),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('restablecerPaleta')
                ->label('Restablecer paleta institucional')
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription('Los cinco colores vuelven a la paleta institucional de CAPDAM. El nombre, el logo y el favicon no cambian.')
                ->action(function () {
                    $this->guardar(Ajuste::PALETA_INSTITUCIONAL);
                    $this->form->fill([...$this->data, ...Ajuste::PALETA_INSTITUCIONAL]);
                }),
        ];
    }

    public function save(): void
    {
        $this->guardar($this->form->getState());
    }

    /** @param array<string, mixed> $datos */
    private function guardar(array $datos): void
    {
        $ajuste = Ajuste::query()->first() ?? Ajuste::porDefecto();
        $ajuste->fill($datos)->save();

        Notification::make()->success()->title('Ajustes guardados')->send();

        // Branding is rendered in the layout, so reload to show the new name, logo and colors.
        $this->redirect(static::getUrl());
    }
}
