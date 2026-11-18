<?php

namespace Tests\Feature;

use App\Enums\RolFirma;
use App\Filament\Resources\Equipos\Pages\CreateEquipo;
use App\Filament\Resources\Firmantes\Pages\ManageFirmantes;
use App\Filament\Resources\Resguardos\Pages\CreateResguardo;
use App\Filament\Resources\Resguardos\Pages\ListResguardos;
use App\Filament\Resources\Resguardos\Pages\ViewResguardo;
use App\Filament\Resources\Resguardos\RelationManagers\FirmasRelationManager;
use App\Models\Equipo;
use App\Models\Firma;
use App\Models\Firmante;
use App\Models\Persona;
use App\Models\Resguardo;
use App\Models\User;
use App\Support\ResguardoPdf;
use Dompdf\Dompdf;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FirmasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    private function vehiculoResguardo(array $attributes = []): Resguardo
    {
        return Resguardo::factory()
            ->for(Equipo::factory()->vehiculo())
            ->create([
                'director_area_cargo' => 'DIRECTOR DE OPERACION',
                'director_area_nombre' => 'ARQ. DIRECTORA DE PRUEBA',
                ...$attributes,
            ]);
    }

    public function test_vehicle_resguardos_need_four_signatures_and_others_two(): void
    {
        Firmante::create(['rol' => RolFirma::Entrega, 'nombre' => 'LIC. JEFA DE BIENES']);

        $vehiculo = $this->vehiculoResguardo();
        $laptop = Resguardo::factory()->create();

        $this->assertEqualsCanonicalizing(
            RolFirma::cases(),
            $vehiculo->firmas->pluck('rol')->all(),
        );
        $this->assertEqualsCanonicalizing(
            [RolFirma::Entrega, RolFirma::Recibe],
            $laptop->firmas->pluck('rol')->all(),
        );

        $recibe = $vehiculo->firmas->firstWhere('rol', RolFirma::Recibe);
        $this->assertSame($vehiculo->persona->nombre, $recibe->nombre);
        $this->assertSame('LIC. JEFA DE BIENES', $vehiculo->firmas->firstWhere('rol', RolFirma::Entrega)->nombre);
        $this->assertSame('DIRECTOR DE OPERACION', $vehiculo->firmas->firstWhere('rol', RolFirma::VoBoDirectorArea)->cargo);
        $this->assertFalse($vehiculo->firmasCompletas());
    }

    public function test_changing_a_firmante_updates_only_pending_signatures(): void
    {
        $firmante = Firmante::create(['rol' => RolFirma::Entrega, 'nombre' => 'ANTERIOR']);
        $firmado = Resguardo::factory()->create();
        $pendiente = Resguardo::factory()->create();
        $firmado->firmas()->where('rol', RolFirma::Entrega)->update(['firmado_en' => now()]);

        $firmante->update(['nombre' => 'NUEVA']);

        $this->assertSame('ANTERIOR', $firmado->firmas()->where('rol', RolFirma::Entrega)->value('nombre'));
        $this->assertSame('NUEVA', $pendiente->firmas()->where('rol', RolFirma::Entrega)->value('nombre'));
    }

    public function test_switching_to_another_device_keeps_signed_rows(): void
    {
        $resguardo = $this->vehiculoResguardo();
        $resguardo->firmas()->where('rol', RolFirma::VoBoDirectorAdministrativo)->update(['firmado_en' => now()]);

        $resguardo->update(['equipo_id' => Equipo::factory()->create()->id]);

        $this->assertEqualsCanonicalizing(
            [RolFirma::VoBoDirectorAdministrativo, RolFirma::Entrega, RolFirma::Recibe],
            $resguardo->firmas()->pluck('rol')->all(),
        );
    }

    public function test_staff_can_mark_signatures_as_signed(): void
    {
        $resguardo = Resguardo::factory()->create();

        foreach ($resguardo->firmas as $firma) {
            Livewire::test(FirmasRelationManager::class, ['ownerRecord' => $resguardo, 'pageClass' => ViewResguardo::class])
                ->callAction(TestAction::make('marcarFirmada')->table($firma), ['firmado_en' => now()->toDateString()])
                ->assertHasNoFormErrors();
        }

        $this->assertTrue($resguardo->fresh()->firmasCompletas());

        Livewire::test(ListResguardos::class)
            ->filterTable('activo', null)
            ->filterTable('firmas_pendientes', true)
            ->assertCanNotSeeTableRecords([$resguardo]);
    }

    public function test_vehicle_resguardo_requires_the_area_director(): void
    {
        $equipo = Equipo::factory()->vehiculo()->create();

        Livewire::test(CreateResguardo::class)
            ->fillForm([
                'persona_id' => Persona::factory()->create()->id,
                'equipo_id' => $equipo->id,
                'fecha_asignacion' => now()->toDateString(),
            ])
            ->call('create')
            ->assertHasFormErrors(['director_area_cargo' => 'required', 'director_area_nombre' => 'required']);
    }

    public function test_staff_can_register_a_vehicle_and_admins_set_firmantes(): void
    {
        Livewire::test(CreateEquipo::class)
            ->fillForm([
                'categoria' => Equipo::CATEGORIA_VEHICULO,
                'numero_serie' => '3N6AD35A3PK851186',
                'numero_inventario' => '00199',
                'placa' => 'FH2048A',
                'anio' => 2023,
                'importe' => '376285.35',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertTrue(Equipo::sole()->esVehiculo());

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ManageFirmantes::class)
            ->callAction('create', ['rol' => RolFirma::Entrega->value, 'nombre' => 'LIC. JEFA DE BIENES'])
            ->assertHasNoFormErrors();

        $this->assertSame('LIC. JEFA DE BIENES', Firmante::nombrePara(RolFirma::Entrega));
    }

    public function test_pdf_is_generated_for_both_formats(): void
    {
        if (! class_exists(Dompdf::class)) {
            $this->markTestSkipped('dompdf is not installed (needs the PHP iconv extension).');
        }

        foreach ([$this->vehiculoResguardo(), Resguardo::factory()->create()] as $resguardo) {
            $pdf = ResguardoPdf::generar($resguardo);
            $this->assertStringStartsWith('%PDF', $pdf);
        }

        $html = view('pdf.resguardo', [
            'resguardo' => $resguardo = $this->vehiculoResguardo(),
            'equipo' => $resguardo->equipo,
            'esVehiculo' => true,
            'firmas' => $resguardo->firmas->keyBy(fn (Firma $f) => $f->rol->value),
            'logo' => null,
            'generado' => now(),
        ])->render();
        $this->assertStringContainsString('RESGUARDO DE VEHICULOS', $html);
        $this->assertStringContainsString('ARQ. DIRECTORA DE PRUEBA', $html);
    }
}
