<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * A PWA de campo.
 *
 * Boa parte destes testes não verifica comportamento: verifica **disciplina**.
 * São invariantes que já foram violadas uma vez e que ninguém percebe ao
 * revisar um diff - zoom bloqueado, alvo de toque encolhido por estilo inline,
 * vermelho de emergência reaproveitado numa ação comum.
 */
class FieldAppTest extends TestCase
{
    private function fieldHtml(): string
    {
        return $this->get('/campo')->assertOk()->getContent();
    }

    /** @return list<string> */
    private function fieldStylesheets(): array
    {
        return array_map(
            fn (\SplFileInfo $file) => $file->getPathname(),
            File::allFiles(resource_path('css/field')),
        );
    }

    // ------------------------------------------------------------ a página

    public function test_field_app_opens_without_authentication(): void
    {
        // Precisa abrir antes de existir qualquer token - inclusive offline,
        // servida pelo service worker.
        $this->get('/campo')->assertOk()->assertSee('Notre Guard');
    }

    public function test_zoom_is_not_blocked(): void
    {
        // maximum-scale=1 viola a WCAG 1.4.4 num aplicativo operado por adultos
        // com pouca luz. Já esteve lá; este teste impede que volte.
        $this->assertStringNotContainsString('maximum-scale', $this->fieldHtml());
    }

    public function test_theme_is_applied_before_the_app_loads(): void
    {
        $html = $this->fieldHtml();

        // O script tem de ser inline e bloqueante: o Alpine carrega diferido, e
        // um flash branco de madrugada custa a visão noturna do vigilante.
        $this->assertStringContainsString("localStorage.getItem('ng.theme')", $html);
        $this->assertStringContainsString('prefers-color-scheme: light', $html);
        $this->assertStringContainsString('prefers-color-scheme: dark', $html);
    }

    public static function screens(): array
    {
        return [
            ['boot'], ['login'], ['home'], ['patrol'],
            ['scan'], ['checklist'], ['incident'], ['queue'],
        ];
    }

    #[DataProvider('screens')]
    public function test_every_screen_partial_is_included(string $screen): void
    {
        // Regressão barata contra um partial esquecido na divisão do Blade.
        $this->assertStringContainsString("screen === '{$screen}'", $this->fieldHtml());
    }

    public function test_markup_carries_no_inline_style(): void
    {
        // Eram 31 atributos style=, com cinco escalas de espaçamento diferentes,
        // e foi por eles que os alvos de toque encolheram.
        $this->assertDoesNotMatchRegularExpression('/\sstyle="/', $this->fieldHtml());
    }

    public function test_every_button_declares_a_component_class(): void
    {
        $html = $this->fieldHtml();

        preg_match_all('/<button\b[^>]*>/', $html, $matches);

        $this->assertNotEmpty($matches[0]);

        // Componentes que definem alvo de toque. Um botão fora desta lista não
        // tem tamanho garantido por nada.
        $sized = 'btn|chip|segmented__option|choice|spine__item|now|tabbar__item|fab|appbar__back';

        foreach ($matches[0] as $button) {
            $this->assertMatchesRegularExpression(
                '/class="[^"]*\b(' . $sized . ')\b/',
                $button,
                "Botão sem classe de componente - o tamanho do alvo depende dela: {$button}",
            );
        }
    }

    public function test_no_native_select_survives_in_the_field_app(): void
    {
        // A roda nativa do celular não é estilizável, ignora o tema, some sob o
        // teclado em PWA instalada e transformava dezessete tipos de ocorrência
        // em dezessete linhas iguais. Escolha em lista é a única forma aqui.
        $this->assertStringNotContainsString(
            '<select',
            $this->fieldHtml(),
            'seleção nativa reintroduzida - use o sheet de escolha em lista.',
        );
    }

    public function test_the_severity_scale_does_not_borrow_the_emergency_red(): void
    {
        // "Crítica" é o extremo da escala, mas um segundo bloco vermelho cheio
        // na tela rouba do botão de emergência exatamente o significado que ele
        // precisa ter. A distinção vem do contorno e do sinal.
        $css = File::get(resource_path('css/field/components/segmented.css'));

        $this->assertStringContainsString('.segmented__option--sev-critical', $css);
        $this->assertStringNotContainsString('background: var(--emergency)', $css);
        $this->assertStringNotContainsString('background: var(--critical-text)', $css);
    }

    // ------------------------------------------------------------- temas

    /** Nomes dos tokens declarados dentro de um bloco. */
    private function tokensIn(string $css, string $selector): array
    {
        $start = strpos($css, $selector);
        $this->assertNotFalse($start, "bloco {$selector} não encontrado em tokens.css");

        $open = strpos($css, '{', $start);
        $depth = 0;
        $end = $open;

        for ($i = $open; $i < strlen($css); $i++) {
            if ($css[$i] === '{') {
                $depth++;
            } elseif ($css[$i] === '}') {
                $depth--;
                if ($depth === 0) {
                    $end = $i;
                    break;
                }
            }
        }

        preg_match_all('/(--[\w-]+)\s*:/', substr($css, $open, $end - $open), $matches);

        return array_unique($matches[1]);
    }

    public function test_the_webview_fallback_covers_every_themed_token(): void
    {
        // Em WebView sem light-dark() um token esquecido não vira o valor
        // escuro: ele fica sem valor nenhum, e a regra que o usa é descartada.
        // Foi assim que --divider sumiu - junto com os fios de todo agrupamento.
        $css = File::get(resource_path('css/field/tokens.css'));

        preg_match_all('/(--[\w-]+)\s*:\s*light-dark\(/', $css, $matches);
        $themed = array_unique($matches[1]);

        $this->assertNotEmpty($themed);

        $fallback = $this->tokensIn($css, '@supports not (color: light-dark(');

        foreach ($themed as $token) {
            $this->assertContains(
                $token,
                $fallback,
                "{$token} não tem valor no fallback: em WebView antiga fica sem valor algum.",
            );
        }
    }

    public function test_night_mode_redefines_everything_except_the_emergency(): void
    {
        // Um token esquecido no modo noturno mantém o valor do tema escuro -
        // ou seja, volta a acender exatamente o que este tema existe para
        // apagar. A emergência é a única exceção, e é deliberada.
        $css = File::get(resource_path('css/field/tokens.css'));

        $night = $this->tokensIn($css, ":root[data-theme='night']");
        $dark = $this->tokensIn($css, '@supports not (color: light-dark(');

        foreach ($dark as $token) {
            if (str_starts_with($token, '--emergency')) {
                $this->assertNotContains(
                    $token,
                    $night,
                    'a emergência não escurece: é a única coisa que continua acesa à noite.',
                );

                continue;
            }

            $this->assertContains($token, $night, "{$token} não foi redefinido no modo noturno.");
        }
    }

    public function test_field_spacing_comes_from_the_container_only(): void
    {
        // `.fieldset` tem gap e `.field + .field` tinha margin-top: as duas se
        // somavam, e o vão projetado para 20px saía com 40. Num formulário de
        // seis campos eram 100px gastos em espaço que ninguém pediu.
        $css = File::get(resource_path('css/field/components/field.css'));

        $this->assertDoesNotMatchRegularExpression(
            '/^\s*\.field \+ \.field\s*\{/m',
            $css,
            'o campo voltou a espaçar a si mesmo, somando com o gap do fieldset.',
        );
    }

    public function test_buttons_are_not_rectangular_slabs(): void
    {
        // Um botão de 343x56 com raio 14 tem proporção raio/altura de 0,25 e
        // lê-se como laje, por mais correto que esteja o alvo de toque. Área de
        // toque e peso visual são coisas distintas.
        $button = File::get(resource_path('css/field/components/button.css'));

        $this->assertMatchesRegularExpression(
            '/\.btn\s*\{[^}]*border-radius:\s*var\(--radius-pill\)/s',
            $button,
            'o botão voltou a ter cantos de bloco.',
        );
    }

    public function test_the_shell_column_cannot_be_widened_by_its_content(): void
    {
        // Sem `minmax(0, 1fr)` a coluna implícita do grid é `auto`, que se
        // dimensiona pelo MAX-CONTENT: um nome de posto longo somado à pastilha
        // de sincronização esticava a barra de topo para 552px num aparelho de
        // 375 e a página inteira rolava na horizontal. O max-width do .app não
        // impedia - quem mandava era o filho mais largo.
        $layout = File::get(resource_path('css/field/layout.css'));

        $this->assertMatchesRegularExpression(
            '/grid-template-columns:\s*minmax\(\s*0\s*,\s*1fr\s*\)/',
            $layout,
            'a casca voltou a deixar o conteúdo definir a largura da coluna.',
        );

        // A marca não pode empilhar: comprimida, ela esticava a altura da barra.
        $this->assertMatchesRegularExpression(
            '/\.appbar__brand\s*\{[^}]*white-space:\s*nowrap/s',
            $layout,
            'a marca da barra de topo voltou a poder quebrar linha.',
        );
    }

    public function test_the_appearance_control_stays_out_of_the_content_area(): void
    {
        // A área central é do turno, da ronda e da ocorrência. Um ajuste no
        // meio dela concorre com o que o vigilante está fazendo - por isso a
        // aparência vive na barra de topo, atrás de um sheet.
        $this->assertStringContainsString(
            'openThemeSheet()',
            File::get(resource_path('views/field/partials/appbar.blade.php')),
            'o ajuste de aparência saiu da barra de topo',
        );

        foreach (File::allFiles(resource_path('views/field/screens')) as $screen) {
            $this->assertStringNotContainsString(
                'setTheme(',
                File::get($screen->getPathname()),
                "{$screen->getFilename()} traz o ajuste de aparência para o conteúdo.",
            );
        }
    }

    public function test_the_blocking_theme_script_knows_the_night_mode(): void
    {
        // O script roda antes do @vite de propósito. Se ele não reconhecer o
        // valor gravado, o aparelho abre no tema errado e só corrige depois que
        // o Alpine carrega - um flash claro na madrugada.
        $this->assertStringContainsString("t === 'night'", $this->fieldHtml());
    }

    // ------------------------------------------------- disciplina do CSS

    public function test_only_the_token_file_holds_literal_colours(): void
    {
        foreach ($this->fieldStylesheets() as $path) {
            if (str_ends_with($path, 'tokens.css')) {
                continue;
            }

            $css = File::get($path);
            $name = basename($path);

            // Cores literais fora de tokens.css significam um valor que não
            // participa dos temas claro e escuro.
            $this->assertDoesNotMatchRegularExpression(
                '/#[0-9a-fA-F]{3,8}\b/',
                $css,
                "{$name} contém cor hexadecimal literal.",
            );
        }
    }

    public function test_filled_red_is_reserved_for_the_emergency(): void
    {
        // A regra existia como comentário e foi violada: "Encerrar turno" usava
        // o mesmo vermelho, na mesma largura, logo abaixo do botão de pânico.
        foreach ($this->fieldStylesheets() as $path) {
            $name = basename($path);

            if (in_array($name, ['tokens.css', 'button.css', 'panic.css', 'fab.css'], true)) {
                continue;
            }

            $this->assertStringNotContainsString(
                'background: var(--emergency)',
                File::get($path),
                "{$name} usa o vermelho de emergência como preenchimento.",
            );
        }

        $button = File::get(resource_path('css/field/components/button.css'));

        $this->assertStringContainsString('.btn--emergency', $button);
        $this->assertStringContainsString('.btn--critical', $button);
    }

    public function test_form_controls_are_large_enough_to_avoid_ios_zoom(): void
    {
        // Sem maximum-scale, o iOS dá zoom ao focar campo com fonte < 16px.
        $tokens = File::get(resource_path('css/field/tokens.css'));
        $field = File::get(resource_path('css/field/components/field.css'));

        $this->assertStringContainsString('--text-base: 17px', $tokens);
        $this->assertStringContainsString('font-size: var(--text-base)', $field);
    }

    public function test_touch_target_tokens_have_no_44px_tier(): void
    {
        $tokens = File::get(resource_path('css/field/tokens.css'));

        $this->assertStringContainsString('--tap-lg: 56px', $tokens);
        $this->assertStringContainsString('--tap-md: 48px', $tokens);

        // Declaração, não menção: o comentário que explica por que o degrau de
        // 44px foi extinto é legítimo e deve continuar existindo.
        $this->assertDoesNotMatchRegularExpression('/:\s*44px\s*;/', $tokens);
    }

    public function test_dark_theme_is_the_fallback_when_light_dark_is_unsupported(): void
    {
        // Em WebView antiga o degradado tem de ser o tema seguro para a ronda
        // noturna, nunca o claro.
        $tokens = File::get(resource_path('css/field/tokens.css'));

        $this->assertStringContainsString('@supports not (color: light-dark(', $tokens);
        $this->assertStringContainsString('--bg: oklch(0.129 0.042 264.695)', $tokens);
    }

    // ------------------------------------------------------------- assets

    public function test_the_font_is_self_hosted(): void
    {
        // Nada pode ser buscado de fora: o aplicativo roda offline.
        $this->assertFileExists(resource_path('fonts/inter/inter-latin-wght-normal.woff2'));

        $fonts = File::get(resource_path('css/field/fonts.css'));

        $this->assertStringContainsString('inter-latin-wght-normal.woff2', $fonts);
        $this->assertStringContainsString('font-display: swap', $fonts);
        // block deixaria o texto invisível se a fonte falhasse.
        $this->assertStringNotContainsString('font-display: block', $fonts);
    }

    public function test_service_worker_covers_the_build_assets_and_prunes_them(): void
    {
        $sw = File::get(public_path('sw.js'));

        $this->assertStringContainsString("startsWith('/build/')", $sw);
        $this->assertStringContainsString('pruneBuildAssets', $sw);
        // O cache precisa mudar de nome, senão o aparelho segue com a casca
        // antiga apontando para assets que não existem mais.
        $this->assertStringContainsString('notre-guard-shell-v2', $sw);
    }

    public function test_pwa_assets_exist_in_public_root(): void
    {
        // Servidos como arquivo estático. O service worker precisa estar na
        // raiz: em subdiretório ele não controla /campo.
        $this->assertFileExists(public_path('sw.js'));
        $this->assertFileExists(public_path('manifest.webmanifest'));

        foreach (['192', '512', 'maskable'] as $variant) {
            $this->assertFileExists(public_path("icons/notre-guard-{$variant}.png"));
        }
    }
}
