<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * As telas do painel são configuradas por closures que só rodam ao renderizar —
 * um rótulo ou relacionamento errado não aparece em nenhum outro teste.
 */
class PanelSmokeTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    public static function resourcePages(): array
    {
        return [
            'unidades' => ['units'],
            'pontos de controle' => ['checkpoints'],
            'postos' => ['posts'],
            'roteiros' => ['patrol-routes'],
            'vigilantes' => ['security-guards'],
            'checklists' => ['checklist-templates'],
            'tipos de ocorrência' => ['incident-types'],
            'turnos' => ['shifts'],
            'rondas' => ['patrols'],
            'ocorrências' => ['incidents'],
            'usuários' => ['users'],
            'RDOs' => ['daily-reports'],
            'retenção de dados' => ['retention-runs'],
        ];
    }

    #[DataProvider('resourcePages')]
    public function test_resource_index_renders(string $slug): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->actingAs($this->admin())
            ->get("/admin/{$slug}")
            ->assertOk();
    }

    public function test_dashboard_renders(): void
    {
        // Os widgets são lazy e não entram no HTML inicial — o conteúdo deles
        // está coberto em DashboardWidgetsTest. Aqui só a página e os filtros.
        $this->seed(DatabaseSeeder::class);

        $this->actingAs($this->admin())
            ->get('/admin')
            ->assertOk()
            ->assertSee('Painel operacional')
            ->assertSee('Período');
    }

    public function test_field_app_is_reachable_without_authentication(): void
    {
        // A PWA precisa abrir antes de existir qualquer token — inclusive
        // offline, servida pelo service worker.
        $this->get('/campo')->assertOk()->assertSee('Notre Guard');
    }

    public function test_pwa_assets_exist_in_public_root(): void
    {
        // Servidos como arquivo estático, não por rota. O service worker precisa
        // estar na raiz: em subdiretório ele não controla /campo.
        $this->assertFileExists(public_path('sw.js'));
        $this->assertFileExists(public_path('manifest.webmanifest'));

        foreach (['192', '512', 'maskable'] as $variant) {
            $this->assertFileExists(public_path("icons/notre-guard-{$variant}.png"));
        }
    }
}
