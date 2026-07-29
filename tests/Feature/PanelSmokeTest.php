<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Filament\Facades\Filament;
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
            'alertas de segurança' => ['safety-alerts'],
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

    /**
     * Percorre todo resource registrado e abre as páginas de criação e edição.
     *
     * Existe porque a listagem passava e a edição estourava: um componente
     * importado do namespace errado só quebra quando o formulário é montado.
     * Como a varredura é automática, resource novo entra coberto sem ninguém
     * lembrar de adicioná-lo aqui.
     */
    public function test_every_resource_form_renders(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAs($this->admin());

        $checked = 0;

        foreach (Filament::getPanel('admin')->getResources() as $resource) {
            $pages = $resource::getPages();

            if (isset($pages['create']) && $resource::canCreate()) {
                $this->get($resource::getUrl('create'))
                    ->assertOk("tela de criação de {$resource}");
                $checked++;
            }

            $record = $resource::getModel()::query()->first();

            if (isset($pages['edit']) && $record) {
                $this->get($resource::getUrl('edit', ['record' => $record]))
                    ->assertOk("tela de edição de {$resource}");
                $checked++;
            }
        }

        $this->assertGreaterThan(10, $checked, 'a varredura deveria alcançar vários formulários');
    }

    public function test_user_edit_form_renders_for_every_role(): void
    {
        // A seção de permissões muda conforme o perfil (o administrador vê um
        // texto em vez das caixas), então cada caminho precisa ser renderizado.
        $this->actingAs($this->admin());

        foreach (array_keys(User::ROLES) as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->get("/admin/users/{$user->id}/edit")
                ->assertOk("edição de usuário com perfil {$role}");
        }
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

    // A PWA de campo tem suíte própria: tests/Feature/FieldAppTest.php.
}
