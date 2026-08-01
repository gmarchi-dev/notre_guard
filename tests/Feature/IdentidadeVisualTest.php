<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Identidade institucional, aplicada às quatro superfícies.
 *
 * O que se protege aqui é a coisa que sempre se perde primeiro num sistema com
 * mais de um painel: a paleta divergir. Já houve TRÊS azuis diferentes em telas
 * do mesmo colégio - Blue no administrativo, Slate na portaria e as escalas do
 * Filament no app de campo.
 *
 * A referência é o sistema de Ativos. Ver docs/15-design-system-extraido.md.
 */
class IdentidadeVisualTest extends TestCase
{
    use RefreshDatabase;

    /** Estrutura: barra lateral, cabeçalho de tabela. */
    private const NAVY = '#0d2144';

    /** Ação: botão, link, item ativo, anel de foco. */
    private const ACCENT = '#1752d9';

    private const SIDEBAR = '#071120';

    public function test_both_panels_use_the_action_blue_as_primary(): void
    {
        // `primary` no Filament pinta botão e estado ativo - ou seja, AÇÃO.
        // O navy de estrutura entra pelo CSS, não por aqui: trocá-los faria o
        // botão primário virar quase preto.
        foreach (['AdminPanelProvider', 'PortariaPanelProvider'] as $painel) {
            $codigo = File::get(app_path("Providers/Filament/{$painel}.php"));

            $this->assertStringContainsString(
                "Color::hex('".self::ACCENT."')",
                $codigo,
                "{$painel} não usa o azul de ação como cor primária.",
            );

            foreach (['#dc2626', '#d97706', '#16a34a'] as $semantica) {
                $this->assertStringContainsString(
                    "Color::hex('{$semantica}')",
                    $codigo,
                    "{$painel} não usa a cor semântica {$semantica}.",
                );
            }
        }
    }

    public function test_the_field_app_shares_the_same_palette(): void
    {
        // Uma paleta, dois sistemas: se os azuis do app de campo divergirem
        // dos azuis dos painéis, o colégio volta a ter mais de uma identidade.
        $tokens = File::get(resource_path('css/field/tokens.css'));

        $this->assertStringContainsString(self::NAVY, $tokens);
        $this->assertStringContainsString(self::ACCENT, $tokens);
    }

    public function test_the_two_blues_keep_distinct_roles(): void
    {
        // O erro fácil desta paleta é confundir estrutura com ação. O navy
        // pinta barra e cabeçalho; o acento pinta o que se toca.
        $paineis = File::get(resource_path('views/filament/identidade.blade.php'));

        $this->assertMatchesRegularExpression(
            '/\.fi-ta-header-(cell|row)[^{]*\{[^}]*background-color:\s*var\(--nc-primary\)/s',
            $paineis,
            'o cabeçalho de tabela saiu do navy de estrutura.',
        );

        $this->assertMatchesRegularExpression(
            '/:focus-visible\s*\{[^}]*outline:\s*2px solid var\(--nc-accent\)/s',
            $paineis,
            'o anel de foco saiu do azul de ação.',
        );
    }

    public function test_the_focus_ring_is_not_gold_anymore(): void
    {
        // O dourado da referência rende 2.74 contra branco: não indica foco.
        // Continua existindo como preenchimento decorativo.
        $campo = File::get(resource_path('css/field/tokens.css'));

        $this->assertStringContainsString('--focus: light-dark('.self::ACCENT, $campo);
        $this->assertStringContainsString('--gold: #c4943e', $campo);
    }

    public function test_the_mark_is_a_single_source_recoloured_by_context(): void
    {
        // Uma marca em currentColor serve os três temas do campo e os dois
        // painéis. Cor fixa exigiria um arquivo por fundo - e é assim que uma
        // marca acaba desatualizada em metade dos lugares.
        foreach (['marca-completa', 'marca-brasao'] as $arquivo) {
            $svg = File::get(resource_path("svg/{$arquivo}.svg"));

            $this->assertStringContainsString('fill="currentColor"', $svg);
            $this->assertStringNotContainsString(
                '#223f51',
                $svg,
                'a cor literal do arquivo original voltou - a marca deixou de acompanhar o tema.',
            );
        }
    }

    public function test_the_panels_carry_the_mark(): void
    {
        foreach (['AdminPanelProvider', 'PortariaPanelProvider'] as $painel) {
            $codigo = File::get(app_path("Providers/Filament/{$painel}.php"));

            $this->assertStringContainsString('brandLogo', $codigo, "{$painel} está sem a marca.");
            $this->assertStringContainsString('notre-guard.svg', $codigo, "{$painel} está sem favicon.");
        }
    }

    public function test_the_sidebar_selectors_still_match_what_filament_renders(): void
    {
        // A identidade entra por render hook, mirando classes do Filament. Se
        // um upgrade renomear qualquer uma delas, o CSS deixa de aplicar EM
        // SILÊNCIO - a barra volta ao cinza e nada quebra. Este teste é o
        // alarme.
        $porteiro = \App\Models\User::factory()->create([
            'role' => \App\Models\User::ROLE_GUARD,
            'permissions' => [\App\Models\User::PERMISSION_KEYS],
        ]);

        $html = $this->actingAs($porteiro)->get('/portaria/key-items')->assertOk()->getContent();

        foreach ([
            'fi-sidebar',
            'fi-sidebar-header',
            'fi-sidebar-item-btn',
            'fi-sidebar-item-badge-ctn',
            'fi-ta-header-cell',
            'fi-logo',
        ] as $classe) {
            $this->assertStringContainsString(
                $classe,
                $html,
                "a classe {$classe} sumiu do Filament - o CSS parou de aplicar.",
            );
        }

        $this->assertStringContainsString('fi-active', $html);

        // E o render hook precisa disparar nas páginas internas, não só no
        // login - é lá dentro que a barra lateral existe.
        $this->assertStringContainsString('--nc-sidebar-bg', $html);
    }

    public function test_the_sidebar_is_the_near_black_of_the_reference(): void
    {
        $css = File::get(resource_path('views/filament/identidade.blade.php'));

        $this->assertStringContainsString('--nc-sidebar-bg: '.self::SIDEBAR, $css);

        // O item ativo é um VÉU translúcido do acento, não um azul sólido:
        // sólido viraria um bloco claro sobre o quase-preto.
        $this->assertStringContainsString('rgba(23, 82, 217, 0.18)', $css);

        // A marca herda a cor do contêiner. Fixar navy a deixaria invisível
        // dentro da barra - já aconteceu uma vez.
        $this->assertMatchesRegularExpression(
            '/\.fi-logo svg\s*\{[^}]*color:\s*inherit/s',
            $css,
            'a marca voltou a ter cor fixa e some dentro da barra escura.',
        );
    }

    public function test_the_login_pages_render_with_the_identity(): void
    {
        // Render de verdade: um erro no partial da marca derrubaria a única
        // porta de entrada dos dois painéis.
        $portaria = $this->get('/portaria/login')->assertOk()->getContent();
        $admin = $this->get('/admin/login')->assertOk()->getContent();

        foreach ([$portaria, $admin] as $html) {
            $this->assertStringContainsString('fi-logo', $html);
            $this->assertStringContainsString('--nc-accent', $html, 'a folha de identidade não foi injetada.');
        }
    }
}
