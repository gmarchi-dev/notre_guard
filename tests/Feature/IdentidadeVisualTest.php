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
 * do mesmo colégio.
 *
 * Ver docs/16-paleta-institucional.md.
 */
class IdentidadeVisualTest extends TestCase
{
    use RefreshDatabase;

    private const NAVY = '#013d53';

    private const GOLD = '#cfb276';

    /** O de marca não tem contraste para texto nem para foco. */
    private const GOLD_DEEP = '#7C6437';

    public function test_both_panels_use_the_brand_navy_as_primary(): void
    {
        foreach (['AdminPanelProvider', 'PortariaPanelProvider'] as $painel) {
            $codigo = File::get(app_path("Providers/Filament/{$painel}.php"));

            $this->assertStringContainsString(
                "Color::hex('".self::NAVY."')",
                $codigo,
                "{$painel} não usa o navy de marca como cor primária.",
            );

            // Semânticas tiradas das rampas, não os padrões do Filament.
            foreach (['#A43D32', '#7C6437', '#2F6B4C'] as $semantica) {
                $this->assertStringContainsString(
                    "Color::hex('{$semantica}')",
                    $codigo,
                    "{$painel} não usa a cor semântica {$semantica}.",
                );
            }
        }
    }

    public function test_the_field_app_shares_the_same_anchors(): void
    {
        // Uma paleta, dois sistemas: se as âncoras divergirem, o colégio volta
        // a ter mais de uma identidade.
        $tokens = File::get(resource_path('css/field/tokens.css'));

        $this->assertStringContainsString(self::NAVY, $tokens);
        $this->assertStringContainsString(self::GOLD, $tokens);
    }

    public function test_the_gold_never_carries_text(): void
    {
        // #cfb276 rende 2.2 contra branco nas DUAS direções: não carrega texto
        // e não recebe texto. É preenchimento decorativo - fio, ponto, ícone.
        $campo = File::get(resource_path('css/field/tokens.css'));
        $paineis = File::get(resource_path('views/filament/identidade.blade.php'));

        $this->assertStringContainsString('--focus: light-dark('.self::GOLD_DEEP, $campo);
        $this->assertStringContainsString('outline: 2px solid var(--nd-gold-deep)', $paineis);

        $this->assertStringNotContainsString(
            'outline: 2px solid var(--nd-gold)',
            $paineis,
            'o anel de foco voltou ao dourado de marca, que não indica foco.',
        );
    }

    public function test_the_neutral_is_cool_grey_everywhere(): void
    {
        // O neutro quente (#F2EDE6) foi testado e recusado por julgamento
        // visual - não por contraste, que é equivalente (1.165 contra 1.153 na
        // separação do cartão branco). A família neutra inteira mudou junto:
        // bege misturado com cinza fica pior que qualquer um dos dois puro.
        $campo = File::get(resource_path('css/field/tokens.css'));
        $paineis = File::get(resource_path('views/filament/identidade.blade.php'));

        $this->assertStringContainsString('--bg: light-dark(#EDEFF0', $campo);
        $this->assertMatchesRegularExpression(
            '/\.fi-body\s*\{[^}]*background-color:\s*var\(--nd-neutral-100\)/s',
            $paineis,
            'o fundo dos painéis saiu do cinza claro.',
        );

        // Nenhum degrau da rampa QUENTE pode voltar a ser superfície, borda ou
        // texto - ela sobrevive só onde é tinta.
        foreach (['#F2EDE6', '#D5C4AC', '#8A7E6B', '#655B4E', '#1D1A14'] as $quente) {
            $this->assertDoesNotMatchRegularExpression(
                '/--(bg|surface|surface-2|border|border-strong|divider|text|text-muted|text-faint|structure-on):[^;]*'.$quente.'/i',
                $campo,
                "o neutro quente {$quente} voltou a ser superfície ou texto.",
            );
        }
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
        $this->assertStringContainsString('--nd-navy', $html);
    }

    public function test_the_active_item_is_marked_by_more_than_colour(): void
    {
        $css = File::get(resource_path('views/filament/identidade.blade.php'));

        // Fio dourado à esquerda: sem ele, a única diferença entre o item
        // ativo e o item sob o cursor seria o tom do azul.
        $this->assertMatchesRegularExpression(
            '/\.fi-active > \.fi-sidebar-item-btn\s*\{[^}]*box-shadow:\s*inset 3px 0 0 var\(--nd-gold\)/s',
            $css,
            'o item ativo perdeu o fio dourado e passou a depender só de cor.',
        );

        // A marca herda a cor do contêiner. Fixar navy a deixaria invisível
        // dentro da barra escura - já aconteceu uma vez.
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
            $this->assertStringContainsString('--nd-gold', $html, 'a folha de identidade não foi injetada.');
        }
    }
}
