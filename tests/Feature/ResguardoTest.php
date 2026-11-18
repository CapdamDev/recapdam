<?php

namespace Tests\Feature;

use App\Filament\Resources\Equipos\Pages\ListEquipos;
use App\Filament\Resources\Personas\Pages\ListPersonas;
use App\Filament\Resources\Resguardos\Pages\CreateResguardo;
use App\Filament\Resources\Resguardos\Pages\EditResguardo;
use App\Filament\Resources\Resguardos\Pages\ListResguardos;
use App\Filament\Resources\Resguardos\Pages\ViewResguardo;
use App\Filament\Resources\Resguardos\RelationManagers\EvidenciasRelationManager;
use App\Models\Equipo;
use App\Models\Evidencia;
use App\Models\Persona;
use App\Models\Resguardo;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ResguardoTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        auth()->logout();

        $this->get('/admin/resguardos')->assertRedirect('/admin/login');
    }

    public function test_list_pages_render(): void
    {
        $resguardo = Resguardo::factory()->create();

        Livewire::test(ListResguardos::class)->assertCanSeeTableRecords([$resguardo]);
        Livewire::test(ListPersonas::class)->assertCanSeeTableRecords([$resguardo->persona]);
        Livewire::test(ListEquipos::class)->assertCanSeeTableRecords([$resguardo->equipo]);
        $this->get(ViewResguardo::getUrl(['record' => $resguardo]))->assertOk();
    }

    public function test_staff_can_create_a_resguardo(): void
    {
        $persona = Persona::factory()->create();
        $equipo = Equipo::factory()->create();

        Livewire::test(CreateResguardo::class)
            ->fillForm([
                'persona_id' => $persona->id,
                'equipo_id' => $equipo->id,
                'fecha_asignacion' => now()->toDateString(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $resguardo = Resguardo::sole();
        $this->assertTrue($resguardo->estaActivo());
        $this->assertSame($this->user->id, $resguardo->registrado_por);
    }

    public function test_a_device_cannot_have_two_open_resguardos(): void
    {
        $existente = Resguardo::factory()->create();

        Livewire::test(CreateResguardo::class)
            ->fillForm([
                'persona_id' => Persona::factory()->create()->id,
                'equipo_id' => $existente->equipo_id,
                'fecha_asignacion' => now()->toDateString(),
            ])
            ->call('create')
            ->assertHasFormErrors(['equipo_id']);

        $this->assertSame(1, Resguardo::count());
    }

    public function test_editing_an_open_resguardo_does_not_conflict_with_itself(): void
    {
        $resguardo = Resguardo::factory()->create();

        Livewire::test(EditResguardo::class, ['record' => $resguardo->getRouteKey()])
            ->fillForm(['observaciones' => 'Cambio de cargador'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Cambio de cargador', $resguardo->fresh()->observaciones);
    }

    public function test_device_can_be_reassigned_after_it_is_returned(): void
    {
        $resguardo = Resguardo::factory()->create(['fecha_asignacion' => now()->subMonth()]);

        Livewire::test(ListResguardos::class)
            ->callAction(TestAction::make('registrarDevolucion')->table($resguardo), [
                'fecha_devolucion' => now()->toDateString(),
            ])
            ->assertHasNoFormErrors();

        $this->assertFalse($resguardo->fresh()->estaActivo());

        Livewire::test(CreateResguardo::class)
            ->fillForm([
                'persona_id' => Persona::factory()->create()->id,
                'equipo_id' => $resguardo->equipo_id,
                'fecha_asignacion' => now()->toDateString(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(1, Resguardo::activos()->count());
    }

    public function test_staff_can_upload_an_evidencia_prefilled_from_the_resguardo(): void
    {
        Storage::fake('local');
        $resguardo = Resguardo::factory()->create();

        Livewire::test(EvidenciasRelationManager::class, [
            'ownerRecord' => $resguardo,
            'pageClass' => ViewResguardo::class,
        ])
            ->callAction(TestAction::make('create')->table(), [
                'archivo' => UploadedFile::fake()->image('etiqueta.jpg'),
            ])
            ->assertHasNoFormErrors();

        $evidencia = Evidencia::sole();
        $this->assertSame($resguardo->equipo->numero_serie, $evidencia->numero_serie);
        $this->assertSame($resguardo->persona->nombre, $evidencia->nombre_persona);
        $this->assertSame('etiqueta.jpg', $evidencia->archivo_nombre_original);
        $this->assertSame($this->user->id, $evidencia->subido_por);
        Storage::disk('local')->assertExists($evidencia->archivo);
    }

    public function test_deleting_a_resguardo_removes_evidencia_files(): void
    {
        Storage::fake('local');
        $resguardo = Resguardo::factory()->create();
        Storage::disk('local')->put('evidencias/a.jpg', 'x');
        Evidencia::factory()->for($resguardo)->create(['archivo' => 'evidencias/a.jpg']);

        $resguardo->delete();

        $this->assertSame(0, Evidencia::count());
        Storage::disk('local')->assertMissing('evidencias/a.jpg');
    }
}
