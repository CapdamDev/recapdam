<?php

namespace Tests\Feature;

use App\Filament\Resources\Equipos\Pages\CreateEquipo;
use App\Filament\Resources\Equipos\Pages\EditEquipo;
use App\Filament\Resources\Partidas\Pages\ListPartidas;
use App\Filament\Resources\Partidas\PartidaResource;
use App\Models\Equipo;
use App\Models\Partida;
use App\Models\User;
use App\Support\ImportadorPartidas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PartidasTest extends TestCase
{
    use RefreshDatabase;

    private const CSV = __DIR__.'/../fixtures/partidas.csv';

    private function importar(): array
    {
        return app(ImportadorPartidas::class)->importar(self::CSV);
    }

    private function partida(string $descripcion): Partida
    {
        return Partida::where('descripcion', $descripcion)->sole();
    }

    public function test_import_builds_the_hierarchy(): void
    {
        $r = $this->importar();

        // 19 data rows minus the blank separator and the exact TABLETAS duplicate.
        $this->assertSame(['creadas' => 17, 'actualizadas' => 0, 'desactivadas' => 0, 'total' => 17], $r);

        $ejecutivo = $this->partida('ESCRITORIO DE MADERA EJECUTIVO');
        $this->assertSame('5-1-1-1-1-1', $ejecutivo->codigo);
        $this->assertSame(6, $ejecutivo->nivel);
        $this->assertSame('MOBILIARIO Y EQUIPO DE ADMINISTRACION › MUEBLES DE OFICINA Y ESTANTERÍA › ESCRITORIO › ESCRITORIO DE MADERA', $ejecutivo->ruta);
        $this->assertSame('1.2.4.1.1', $ejecutivo->cuenta_contable);
        $this->assertTrue($ejecutivo->seleccionable);

        // Groups with children can't be assigned; a leaf whose parent row is missing can.
        $this->assertFalse($this->partida('ESCRITORIO DE MADERA')->seleccionable);
        $this->assertTrue($this->partida('ESCRITORIO MODULAR')->seleccionable);

        // The duplicated TABLETAS row contributes the account the first one lacked.
        $this->assertSame('1.2.4.1.3', $this->partida('TABLETAS')->cuenta_contable);

        // Same code, different products: both kept, notes imported.
        $this->assertSame(2, Partida::where('codigo', '5-6-2-3-4-1')->count());
        $this->assertSame('MAL CASIFICADO', $this->partida('CUERPO DE TAZONES')->nota);

        $this->assertTrue($this->partida('CAMIONETA CHASIS')->esVehiculo());
        $this->assertFalse($ejecutivo->esVehiculo());
    }

    public function test_reimport_is_idempotent_and_deactivates_missing_rows(): void
    {
        $this->importar();
        $this->assertSame(0, $this->importar()['creadas']);

        $equipo = Equipo::factory()->create(['partida_id' => $this->partida('SILLA SECRETARIAL')->id]);

        $sinSilla = tempnam(sys_get_temp_dir(), 'partidas');
        file_put_contents($sinSilla, implode("\n", array_filter(file(self::CSV, FILE_IGNORE_NEW_LINES), fn ($l) => ! str_contains($l, 'SILLA SECRETARIAL'))));
        $r = app(ImportadorPartidas::class)->importar($sinSilla);
        unlink($sinSilla);

        $this->assertSame(1, $r['desactivadas']);
        $this->assertFalse($this->partida('SILLA SECRETARIAL')->activo);
        $this->assertSame($equipo->partida_id, $equipo->fresh()->partida_id);
    }

    public function test_import_rejects_files_without_the_catalog_columns(): void
    {
        $archivo = tempnam(sys_get_temp_dir(), 'partidas');
        file_put_contents($archivo, "nombre,precio\nsilla,10\n");

        $this->expectExceptionMessage('Faltan columnas');

        try {
            app(ImportadorPartidas::class)->importar($archivo);
        } finally {
            unlink($archivo);
        }
    }

    public function test_search_matches_words_in_any_order_accents_and_codes(): void
    {
        $this->importar();
        $buscar = fn (string $q) => Partida::seleccionables()->buscar($q)->pluck('descripcion')->sort()->values()->all();

        $this->assertSame(['ESCRITORIO DE MADERA SECRETARIAL'], $buscar('secretarial madera'));
        $this->assertContains('ESCRITORIO MODULAR', $buscar('estanteria')); // matches "ESTANTERÍA" in the path
        $this->assertSame(['ESCRITORIO DE MADERA EJECUTIVO', 'ESCRITORIO DE MADERA SECRETARIAL'], $buscar('5-1-1-1-1'));
        $this->assertNotContains('ESCRITORIO DE MADERA', $buscar('escritorio')); // groups aren't assignable
        $this->assertSame([], $buscar('100%'));

        // The description match ranks first.
        $this->assertSame('SILLA SECRETARIAL', Partida::seleccionables()->buscar('silla')->value('descripcion'));
    }

    public function test_equipo_takes_its_category_and_vehicle_flag_from_the_product(): void
    {
        $this->importar();
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateEquipo::class)
            ->fillForm([
                'partida_id' => $this->partida('CAMIONETA CHASIS')->id,
                'numero_serie' => '3N6AD35A3PK851186',
                'placa' => 'FH2048A',
            ])
            ->assertFormFieldVisible('placa')
            ->call('create')
            ->assertHasNoFormErrors();

        $equipo = Equipo::sole();
        $this->assertSame('VEHICULOS Y EQUIPO DE TRANSPORTE', $equipo->categoria);
        $this->assertTrue($equipo->esVehiculo());

        Livewire::test(EditEquipo::class, ['record' => $equipo->getRouteKey()])
            ->fillForm(['partida_id' => $this->partida('SILLA SECRETARIAL')->id])
            ->assertFormFieldHidden('placa')
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('MOBILIARIO Y EQUIPO DE ADMINISTRACION', $equipo->fresh()->categoria);
        $this->assertFalse($equipo->fresh()->esVehiculo());
    }

    public function test_only_assignable_active_products_can_be_chosen(): void
    {
        $this->importar();
        $this->actingAs(User::factory()->create());

        foreach (['ESCRITORIO DE MADERA'] as $grupo) {
            Livewire::test(CreateEquipo::class)
                ->fillForm(['partida_id' => $this->partida($grupo)->id, 'numero_serie' => 'X1'])
                ->call('create')
                ->assertHasFormErrors(['partida_id']);
        }

        $silla = $this->partida('SILLA SECRETARIAL');
        $equipo = Equipo::factory()->create(['partida_id' => $silla->id]);
        $silla->update(['activo' => false]);

        // An equipo keeps a product that left the catalog, but new equipos can't pick it.
        Livewire::test(EditEquipo::class, ['record' => $equipo->getRouteKey()])
            ->fillForm(['marca' => 'OTRA'])
            ->call('save')
            ->assertHasNoFormErrors();

        Livewire::test(CreateEquipo::class)
            ->fillForm(['partida_id' => $silla->id, 'numero_serie' => 'X2'])
            ->call('create')
            ->assertHasFormErrors(['partida_id']);
    }

    public function test_catalog_page_search_and_admin_only_import(): void
    {
        $this->importar();

        $this->actingAs(User::factory()->create());
        $this->get(PartidaResource::getUrl())->assertOk();
        Livewire::test(ListPartidas::class)
            ->searchTable('madera secretarial')
            ->assertCanSeeTableRecords([$this->partida('ESCRITORIO DE MADERA SECRETARIAL')])
            ->assertCanNotSeeTableRecords([$this->partida('SILLA SECRETARIAL')])
            ->assertActionHidden('importar');

        Storage::fake('local');
        $this->actingAs(User::factory()->admin()->create());
        Livewire::test(ListPartidas::class)
            ->callAction('importar', ['archivo' => UploadedFile::fake()->createWithContent('partidas.csv', file_get_contents(self::CSV))])
            ->assertHasNoFormErrors()
            ->assertNotified('Catálogo importado');

        $this->assertSame([], Storage::disk('local')->allFiles('importaciones'));
    }
}
