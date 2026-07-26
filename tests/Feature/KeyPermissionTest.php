<?php

namespace Tests\Feature;

use App\Filament\Portaria\Resources\KeyItems\KeyItemResource as PortariaKeyItemResource;
use App\Filament\Resources\KeyItems\KeyItemResource as AdminKeyItemResource;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * O módulo de chaves é liberado por permissão individual, não por perfil.
 *
 * Não é todo vigilante que fica na portaria mexendo no quadro, e não é todo
 * supervisor que precisa disso.
 */
class KeyPermissionTest extends TestCase
{
    use RefreshDatabase;

    private function portaria(): \Filament\Panel
    {
        return Filament::getPanel('portaria');
    }

    private function withKeys(string $role): User
    {
        return User::factory()->create([
            'role' => $role,
            'permissions' => [User::PERMISSION_KEYS],
        ]);
    }

    // ----------------------------------------------------------- sem permissão

    public function test_nobody_gets_the_keys_module_by_default(): void
    {
        // Nenhuma permissão é concedida automaticamente — nem pelo perfil, nem
        // pela migração.
        foreach ([User::ROLE_GUARD, User::ROLE_SUPERVISOR, User::ROLE_UNIT_MANAGER] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->assertFalse(
                $user->hasPermission(User::PERMISSION_KEYS),
                "perfil {$role} não deveria ter a permissão por padrão",
            );
            $this->assertFalse($user->canAccessPanel($this->portaria()));
        }
    }

    public function test_guard_without_permission_cannot_reach_the_portaria_panel(): void
    {
        $guard = User::factory()->create(['role' => User::ROLE_GUARD]);

        $this->actingAs($guard)->get('/portaria')->assertForbidden();
    }

    public function test_supervisor_without_permission_loses_the_key_screens(): void
    {
        // Vale para a supervisão também: a permissão não é hierárquica.
        $this->actingAs(User::factory()->create(['role' => User::ROLE_SUPERVISOR]));

        $this->assertFalse(AdminKeyItemResource::canAccess());

        $this->get('/admin/key-items')->assertForbidden();
    }

    // ----------------------------------------------------------- com permissão

    public function test_guard_with_permission_operates_the_key_board(): void
    {
        $porteiro = $this->withKeys(User::ROLE_GUARD);

        $this->assertTrue($porteiro->canAccessPanel($this->portaria()));

        $this->actingAs($porteiro);

        $this->assertTrue(PortariaKeyItemResource::canAccess());
        $this->get('/portaria/key-items')->assertOk();
    }

    public function test_permission_does_not_open_the_admin_panel(): void
    {
        // Conceder chaves a um vigilante não pode virar acesso à operação das
        // duas unidades.
        $porteiro = $this->withKeys(User::ROLE_GUARD);

        $this->assertFalse($porteiro->canAccessPanel(Filament::getPanel('admin')));

        $this->actingAs($porteiro)->get('/admin')->assertForbidden();
    }

    public function test_supervisor_with_permission_sees_the_key_registry(): void
    {
        $this->actingAs($this->withKeys(User::ROLE_SUPERVISOR));

        $this->assertTrue(AdminKeyItemResource::canAccess());
        $this->get('/admin/key-items')->assertOk();
    }

    // -------------------------------------------------------------- exceções

    public function test_admin_has_every_module_by_definition(): void
    {
        // Do contrário seria possível revogar a própria capacidade de conceder
        // permissões e travar o sistema.
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'permissions' => []]);

        $this->assertTrue($admin->hasPermission(User::PERMISSION_KEYS));
        $this->assertTrue($admin->canAccessPanel($this->portaria()));
        $this->assertSame(['todas (administrador)'], $admin->grantedPermissionLabels());
    }

    public function test_inactive_user_loses_every_permission(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_GUARD,
            'permissions' => [User::PERMISSION_KEYS],
            'active' => false,
        ]);

        $this->assertFalse($user->hasPermission(User::PERMISSION_KEYS));
        $this->assertFalse($user->canAccessPanel($this->portaria()));
    }

    public function test_inactive_admin_loses_the_bypass_too(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'active' => false]);

        $this->assertFalse($admin->hasPermission(User::PERMISSION_KEYS));
    }

    // ------------------------------------------------------------------ gate

    public function test_permission_is_exposed_as_a_laravel_gate(): void
    {
        $porteiro = $this->withKeys(User::ROLE_GUARD);
        $outro = User::factory()->create(['role' => User::ROLE_GUARD]);

        $this->assertTrue($porteiro->can(User::PERMISSION_KEYS));
        $this->assertFalse($outro->can(User::PERMISSION_KEYS));
    }

    public function test_unknown_permission_is_never_granted(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_GUARD,
            'permissions' => ['modulo.inexistente'],
        ]);

        $this->assertFalse($user->hasPermission(User::PERMISSION_KEYS));
        // Permissão fora da lista conhecida não aparece como módulo liberado.
        $this->assertSame([], $user->grantedPermissionLabels());
    }

    public function test_seeded_users_can_operate_keys(): void
    {
        // O ambiente de demonstração precisa continuar funcionando depois de a
        // permissão passar a ser obrigatória.
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $vigilante = User::where('email', 'vigilante@notreguard.local')->firstOrFail();
        $supervisao = User::where('email', 'supervisao@notreguard.local')->firstOrFail();

        $this->assertTrue($vigilante->hasPermission(User::PERMISSION_KEYS));
        $this->assertTrue($supervisao->hasPermission(User::PERMISSION_KEYS));
    }
}
