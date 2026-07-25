<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_supervision_reach_the_panel(): void
    {
        $panel = Filament::getPanel('admin');

        foreach ([User::ROLE_ADMIN, User::ROLE_SUPERVISOR, User::ROLE_UNIT_MANAGER] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->assertTrue($user->canAccessPanel($panel), "perfil {$role} deveria acessar o painel");
        }
    }

    public function test_guard_does_not_reach_the_panel(): void
    {
        // O vigilante usa a PWA de campo. Se ele entrasse aqui veria dados de
        // todas as unidades.
        $guard = User::factory()->create(['role' => User::ROLE_GUARD]);

        $this->assertFalse($guard->canAccessPanel(Filament::getPanel('admin')));
    }

    public function test_only_admin_manages_accounts(): void
    {
        // Criar login e trocar perfil é atribuição de administrador. Supervisão
        // e gestor de unidade não mexem no acesso de ninguém.
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));
        $this->assertTrue(UserResource::canAccess());

        foreach ([User::ROLE_SUPERVISOR, User::ROLE_UNIT_MANAGER] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]));
            $this->assertFalse(UserResource::canAccess(), "perfil {$role} não deveria gerir contas");
        }
    }

    public function test_supervision_cannot_reach_the_users_screen(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_SUPERVISOR]))
            ->get('/admin/users')
            ->assertForbidden();
    }

    public function test_inactive_user_is_locked_out_even_with_admin_role(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN, 'active' => false]);

        $this->assertFalse($user->canAccessPanel(Filament::getPanel('admin')));
    }
}
