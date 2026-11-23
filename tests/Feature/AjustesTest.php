<?php

namespace Tests\Feature;

use App\Filament\Pages\Ajustes;
use App\Models\Ajuste;
use App\Models\User;
use Filament\Support\Colors\Color;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AjustesTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admins_can_open_ajustes(): void
    {
        $this->actingAs(User::factory()->create())->get(Ajustes::getUrl())->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get(Ajustes::getUrl())->assertOk();
    }

    public function test_defaults_are_the_institutional_palette(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(Ajustes::class)
            ->assertSchemaStateSet(['nombre_sistema' => Ajuste::NOMBRE_DEFAULT, ...Ajuste::PALETA_INSTITUCIONAL]);
    }

    // Panel HTTP checks run in their own tests: Filament registers panel colors once per process,
    // so a Livewire::test() earlier in the same test would leave the previous colors in place.
    public function test_panel_uses_the_institutional_palette_by_default(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin')
            ->assertSee('--primary-600:'.Color::convertToOklch(Ajuste::PALETA_INSTITUCIONAL['color_principal']).';', false)
            ->assertSee(Ajuste::PALETA_INSTITUCIONAL['color_acento'], false);
    }

    public function test_admin_saves_name_colors_and_logo(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(Ajustes::class)
            ->fillForm([
                'nombre_sistema' => 'Patrimonio CAPDAM',
                'color_principal' => '#123456',
                'color_principal_claro' => null,
                'logo' => UploadedFile::fake()->image('logo.png', 512, 512),
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $ajuste = Ajuste::sole();
        $this->assertSame('Patrimonio CAPDAM', $ajuste->nombre_sistema);
        $this->assertNull($ajuste->color_principal_claro);
        Storage::disk('public')->assertExists($ajuste->logo);
    }

    public function test_panel_shows_saved_name_colors_and_logo(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('ajustes/logo.png', 'x');
        Ajuste::create([...Ajuste::PALETA_INSTITUCIONAL, 'nombre_sistema' => 'Patrimonio CAPDAM', 'color_principal' => '#123456', 'logo' => 'ajustes/logo.png']);

        $this->actingAs(User::factory()->create())
            ->get('/admin')
            ->assertSee('Patrimonio CAPDAM')
            ->assertSee('--primary-600:'.Color::convertToOklch('#123456').';', false)
            ->assertSee(Storage::disk('public')->url('ajustes/logo.png'), false);
    }

    public function test_settings_survive_the_database_cache(): void
    {
        config(['cache.default' => 'database']);
        Ajuste::create([...Ajuste::PALETA_INSTITUCIONAL, 'nombre_sistema' => 'Patrimonio CAPDAM']);

        Ajuste::actual(); // fills the cache
        $this->app->forgetInstance('cache');
        $this->app->forgetInstance('cache.store');

        $this->assertSame('Patrimonio CAPDAM', Ajuste::actual()->nombre_sistema);
    }

    public function test_invalid_colors_are_rejected(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(Ajustes::class)
            ->fillForm(['color_principal' => 'rojo'])
            ->call('save')
            ->assertHasFormErrors(['color_principal']);
    }

    public function test_restore_institutional_palette_keeps_the_name(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Ajuste::create(['nombre_sistema' => 'Otro', ...Ajuste::PALETA_INSTITUCIONAL, 'color_principal' => '#000000', 'color_texto' => '#ffffff']);

        Livewire::test(Ajustes::class)->callAction('restablecerPaleta');

        $ajuste = Ajuste::sole();
        $this->assertSame('Otro', $ajuste->nombre_sistema);
        $this->assertSame(Ajuste::PALETA_INSTITUCIONAL['color_principal'], $ajuste->color_principal);
        $this->assertSame(Ajuste::PALETA_INSTITUCIONAL['color_texto'], $ajuste->color_texto);
    }
}
