{{--
    Identidade institucional nos painéis Filament.

    A paleta vem do sistema de Ativos do colégio - ver
    docs/15-design-system-extraido.md, com os tokens de origem, o contraste
    medido e as decisões sobre os pares que reprovam.

    Injetado por render hook, e não como tema Vite do Filament: um tema exigiria
    pipeline de build própria mais `filament:assets` no deploy.

    O RISCO DESSA ESCOLHA É CONHECIDO: o CSS mira classes do Filament
    (`fi-sidebar-item-btn`, `fi-active`, `fi-logo`). Se um upgrade renomear
    qualquer uma, o estilo deixa de aplicar EM SILÊNCIO. Há teste que renderiza
    uma página autenticada e confere que cada classe ainda existe.

    A cor primária não vem daqui: vem do FilamentColor, que gera a escala
    inteira. Este arquivo cobre o que ele não alcança - estrutura, forma e foco.
--}}
<style>
    :root {
        /* Nomenclatura de origem preservada, para os dois sistemas falarem a
           mesma língua. */
        --nc-primary: #0d2144;
        --nc-accent: #1752d9;
        --nc-gold: #c4943e;

        --nc-surface: #ffffff;
        --nc-surface-2: #edf0f7;
        --nc-border: #c0cade;
        --nc-border-light: #e2e8f0;
        --nc-text: #0f172a;
        --nc-text-2: #475569;

        --nc-sidebar-bg: #071120;
        --nc-sidebar-text: #94a3b8;
        --nc-sidebar-active: rgba(23, 82, 217, 0.18);

        /* A escala de raio da referência. */
        --radius-xs: 4px;
        --radius-sm: 6px;
        --radius-md: 10px;
        --radius-lg: 14px;
        --radius-xl: 20px;
    }

    /* O fundo azulado frio é o que faz o cartão branco parecer elevado quase
       sem sombra - é a base do sistema, não um detalhe. */
    .fi-body {
        background-color: var(--nc-surface-2);
    }

    /*
     * Anel de foco no ACENTO, não no dourado.
     *
     * O dourado da paleta rende 2.74 contra branco: não serve para indicar
     * foco. O acento é a cor de ação da referência e mede 6.47.
     */
    .fi-body :focus-visible {
        outline: 2px solid var(--nc-accent);
        outline-offset: 2px;
    }

    /*
     * Barra lateral quase preta - o eixo vertical da referência.
     *
     * O item ativo é um VÉU translúcido do acento, não um azul sólido: assim
     * ele se destaca sem virar um bloco claro sobre o quase-preto.
     */
    .fi-sidebar,
    .fi-sidebar-header {
        background-color: var(--nc-sidebar-bg);
        border-color: rgba(255, 255, 255, 0.06);
    }

    .fi-sidebar .fi-sidebar-item-btn,
    .fi-sidebar .fi-sidebar-group-btn,
    .fi-sidebar .fi-sidebar-group-label {
        /* Um degrau acima do `--nc-sidebar-text` de origem (#94a3b8, 7.38):
           aqui vale a folga, porque a portaria opera o dia inteiro nesta
           barra. */
        color: #cbd5e1;
    }

    .fi-sidebar .fi-sidebar-item-btn > .fi-icon {
        color: var(--nc-sidebar-text);
    }

    .fi-sidebar .fi-sidebar-item-btn:hover,
    .fi-sidebar .fi-sidebar-group-btn:hover {
        background-color: rgba(255, 255, 255, 0.05);
        color: #ffffff;
    }

    .fi-sidebar .fi-sidebar-item.fi-active > .fi-sidebar-item-btn {
        background-color: var(--nc-sidebar-active);
        color: #ffffff;
        font-weight: 600;
    }

    .fi-sidebar .fi-sidebar-item.fi-active > .fi-sidebar-item-btn > .fi-icon {
        color: #9dbcff;
    }

    .fi-sidebar .fi-sidebar-item-badge-ctn .fi-badge {
        background-color: var(--nc-accent);
        color: #ffffff;
    }

    .fi-sidebar .fi-sidebar-footer,
    .fi-sidebar .fi-sidebar-nav-groups > * + * {
        border-color: rgba(255, 255, 255, 0.06);
    }

    /*
     * Cabeçalho de tabela no navy de ESTRUTURA, não no acento.
     *
     * É o que amarra tabela e barra lateral como uma coisa só - "estrutura" -
     * e deixa o acento livre para significar ação.
     */
    .fi-ta-header-cell,
    .fi-ta-header-row {
        background-color: var(--nc-primary);
    }

    .fi-ta-header-cell,
    .fi-ta-header-cell .fi-ta-header-cell-label,
    .fi-ta-header-cell button {
        color: #ffffff;
    }

    /* A marca herda a cor de quem a contém: navy no login, clara na barra.
       Fixá-la em navy a deixaria invisível dentro da barra quase preta. */
    .fi-logo {
        display: flex;
        align-items: center;
        gap: 0.625rem;
    }

    .fi-logo svg {
        display: block;
        height: 1.75rem;
        width: auto;
        color: inherit;
    }

    .fi-simple-layout .fi-logo,
    .fi-simple-main .fi-logo {
        color: var(--nc-primary);
    }

    .fi-sidebar-header .fi-logo,
    .fi-sidebar-header .fi-logo span {
        color: #ffffff;
    }
</style>
