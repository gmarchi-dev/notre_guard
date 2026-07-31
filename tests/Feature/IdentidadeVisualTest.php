<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Identidade institucional, aplicada às quatro superfícies.
 *
 * O que se protege aqui é a coisa que sempre se perde primeiro num sistema com
 * mais de um painel: a paleta divergir. A portaria usava Slate, o administrativo
 * usava Blue e o app de campo usava as escalas do Filament - três azuis
 * diferentes em três telas do mesmo colégio.
 *
 * Ver docs/design-system.md.
 */
class IdentidadeVisualTest extends TestCase
{
    use RefreshDatabase;

    private const NAVY = '#013d53';

    private const GOLD_500 = '#cfb276';

    private const GOLD_700 = '#a88a52';

    public function test_both_panels_use_the_institutional_navy(): void
    {
        foreach (['AdminPanelProvider', 'PortariaPanelProvider'] as $painel) {
            $codigo = File::get(app_path("Providers/Filament/{$painel}.php"));

            $this->assertStringContainsString(
                "Color::hex('".self::NAVY."')",
                $codigo,
                "{$painel} não usa o navy institucional como cor primária.",
            );

            // As semânticas do design system também, senão o Filament cai nos
            // vermelhos e verdes padrão dele.
            foreach (['#b3423a', '#b8873a', '#2f7a52'] as $semantica) {
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
        // Uma paleta, dois sistemas: se o navy do app de campo divergir do
        // navy dos painéis, o colégio passa a ter dois azuis.
        $tokens = File::get(resource_path('css/field/tokens.css'));

        $this->assertStringContainsString(self::NAVY, $tokens);
        $this->assertStringContainsString(self::GOLD_500, $tokens);
        $this->assertStringContainsString(self::GOLD_700, $tokens);
    }

    public function test_the_gold_focus_ring_reaches_every_surface(): void
    {
        // O design system é explícito: foco em gold-700, nunca gold-500, que
        // não tem contraste para indicar foco em fundo claro.
        $campo = File::get(resource_path('css/field/base.css'));
        $paineis = File::get(resource_path('views/filament/identidade.blade.php'));

        $this->assertStringContainsString('outline: 2px solid var(--focus)', $campo);
        $this->assertStringContainsString('--focus: light-dark('.self::GOLD_700, File::get(resource_path('css/field/tokens.css')));

        $this->assertStringContainsString('outline: 2px solid var(--nd-gold-700)', $paineis);
        $this->assertStringContainsString('outline-color: var(--nd-gold-500)', $paineis);
    }

    public function test_the_mark_is_a_single_source_recoloured_by_context(): void
    {
        // Uma marca em currentColor serve os quatro temas do campo e os dois
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
            'fi-logo',
        ] as $classe) {
            $this->assertStringContainsString(
                $classe,
                $html,
                "a classe {$classe} sumiu do Filament - o CSS da barra lateral parou de aplicar.",
            );
        }

        // O item ativo é o que carrega o fio dourado; sem esta classe, "onde
        // você está" deixa de ser marcado.
        $this->assertStringContainsString('fi-active', $html);

        // E o render hook precisa disparar nas páginas internas, não só no
        // login - é lá dentro que a barra lateral existe.
        $this->assertStringContainsString('--nd-navy-900', $html);
    }

    public function test_the_sidebar_is_navy_not_grey(): void
    {
        $css = File::get(resource_path('views/filament/identidade.blade.php'));

        $this->assertMatchesRegularExpression(
            '/\.fi-sidebar[^{]*\{[^}]*background-color:\s*var\(--nd-navy-900\)/s',
            $css,
            'a barra lateral saiu do navy institucional.',
        );

        // A marca herda a cor do contêiner. Fixar navy nela a deixaria
        // invisível dentro da barra navy - quase aconteceu.
        $this->assertMatchesRegularExpression(
            '/\.fi-logo svg\s*\{[^}]*color:\s*inherit/s',
            $css,
            'a marca voltou a ter cor fixa e some dentro da barra navy.',
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
            $this->assertStringContainsString('--nd-gold-700', $html, 'a folha de identidade não foi injetada.');
        }
    }
}
