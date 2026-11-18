<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Filament\Resources\Equipos\Pages\ListEquipos;
use App\Filament\Resources\Firmantes\FirmanteResource;
use App\Filament\Resources\Resguardos\Pages\ViewResguardo;
use App\Filament\Resources\Resguardos\RelationManagers\EvidenciasRelationManager;
use App\Filament\Resources\Resguardos\RelationManagers\FirmasRelationManager;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Filament\Resources\Users\UserResource;
use App\Models\Equipo;
use App\Models\Evidencia;
use App\Models\Resguardo;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class RolesTest extends TestCase
{
    use RefreshDatabase;

    public function test_patrimonio_cannot_open_configuration_pages(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(UserResource::getUrl())->assertForbidden();
        $this->get(FirmanteResource::getUrl())->assertForbidden();
    }

    public function test_admin_can_open_configuration_pages(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->get(UserResource::getUrl())->assertOk();
        $this->get(FirmanteResource::getUrl())->assertOk();
    }

    public function test_only_admins_can_delete_records(): void
    {
        $equipo = Equipo::factory()->create();
        $resguardo = Resguardo::factory()->create();
        $evidencia = Evidencia::factory()->for($resguardo)->create();
        $relation = ['ownerRecord' => $resguardo, 'pageClass' => ViewResguardo::class];

        $this->actingAs(User::factory()->create());
        Livewire::test(ListEquipos::class)->assertActionHidden(TestAction::make('delete')->table($equipo));
        Livewire::test(EvidenciasRelationManager::class, $relation)->assertActionHidden(TestAction::make('delete')->table($evidencia));

        $this->actingAs(User::factory()->admin()->create());
        Livewire::test(ListEquipos::class)->callAction(TestAction::make('delete')->table($equipo));
        $this->assertModelMissing($equipo);
    }

    public function test_only_admins_can_unmark_a_signature(): void
    {
        $resguardo = Resguardo::factory()->create();
        $firma = $resguardo->firmas->first();
        $firma->update(['firmado_en' => now()]);
        $relation = ['ownerRecord' => $resguardo, 'pageClass' => ViewResguardo::class];

        $this->actingAs(User::factory()->create());
        Livewire::test(FirmasRelationManager::class, $relation)
            ->assertActionHidden(TestAction::make('desmarcar')->table($firma));

        $this->actingAs(User::factory()->admin()->create());
        Livewire::test(FirmasRelationManager::class, $relation)
            ->callAction(TestAction::make('desmarcar')->table($firma));
        $this->assertNull($firma->fresh()->firmado_en);
    }

    public function test_admin_creates_a_patrimonio_user_who_can_log_in(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ManageUsers::class)
            ->callAction('create', [
                'name' => 'Capturista',
                'email' => 'capturista@example.com',
                'rol' => RolUsuario::Patrimonio->value,
                'activo' => true,
                'password' => 'secreto123',
                'password_confirmation' => 'secreto123',
            ])
            ->assertHasNoFormErrors();

        $nuevo = User::firstWhere('email', 'capturista@example.com');
        $this->assertSame(RolUsuario::Patrimonio, $nuevo->rol);
        $this->assertTrue(Hash::check('secreto123', $nuevo->password));

        auth()->logout();
        $this->actingAs($nuevo)->get('/admin/resguardos')->assertOk();
    }

    public function test_deactivated_users_are_locked_out(): void
    {
        $this->actingAs(User::factory()->create(['activo' => false]))
            ->get('/admin/resguardos')
            ->assertForbidden();
    }

    public function test_admin_cannot_demote_or_deactivate_themselves(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(ManageUsers::class)
            ->assertActionHidden(TestAction::make('alternarActivo')->table($admin))
            ->callAction(TestAction::make('edit')->table($admin), [
                'rol' => RolUsuario::Patrimonio->value,
                'activo' => false,
            ]);

        $admin->refresh();
        $this->assertTrue($admin->esAdmin());
        $this->assertTrue($admin->activo);
    }

    public function test_editing_a_user_without_a_password_keeps_the_old_one(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $user = User::factory()->create(['password' => 'original123']);

        Livewire::test(ManageUsers::class)
            ->callAction(TestAction::make('edit')->table($user), ['name' => 'Otro nombre'])
            ->assertHasNoFormErrors();

        $this->assertTrue(Hash::check('original123', $user->fresh()->password));
    }
}
