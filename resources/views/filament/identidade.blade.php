{{--
    Identidade institucional nos painéis Filament.

    Âncoras de marca: navy #013d53 e dourado #cfb276, com as rampas fornecidas
    dando os degraus. Ver docs/16-paleta-institucional.md, com o contraste
    medido e as derivações justificadas.

    Injetado por render hook, e não como tema Vite do Filament: um tema exigiria
    pipeline de build própria mais `filament:assets` no deploy.

    O RISCO DESSA ESCOLHA É CONHECIDO: o CSS mira classes do Filament
    (`fi-sidebar-item-btn`, `fi-active`, `fi-logo`). Se um upgrade renomear
    qualquer uma, o estilo deixa de aplicar EM SILÊNCIO. Há teste que renderiza
    uma página autenticada e confere que cada classe ainda existe.
--}}
<style>
    :root {
        /* Âncoras de marca. */
        --nd-navy: #013d53;
        --nd-gold: #cfb276;

        /* Rampa teal. */
        --nd-teal-100: #D0EBFC;
        --nd-teal-500: #1B5E7E;
        --nd-teal-700: #031823;

        /* Rampa quente - o neutro do sistema não é cinza. */
        --nd-warm-100: #F2EDE6;
        --nd-warm-200: #D5C4AC;
        --nd-warm-500: #655B4E;
        --nd-warm-700: #1D1A14;

        /* Dourado escuro: o de marca não tem contraste para texto nem foco. */
        --nd-gold-deep: #7C6437;

        --radius-xs: 4px;
        --radius-sm: 6px;
        --radius-md: 10px;
        --radius-lg: 14px;
        --radius-xl: 20px;
    }

    /* Neutro QUENTE, não cinza: é o que dá a leitura de papel e distingue o
       sistema de um painel administrativo genérico. */
    .fi-body {
        background-color: var(--nd-warm-100);
    }

    /*
     * Anel de foco no dourado ESCURO.
     *
     * O dourado de marca rende 2.2 contra branco: não indica foco. O degrau
     * escuro da rampa resolve sem sair da família.
     */
    .fi-body :focus-visible {
        outline: 2px solid var(--nd-gold-deep);
        outline-offset: 2px;
    }

    .dark .fi-body :focus-visible {
        outline-color: #F0CE90;
    }

    /*
     * Barra lateral no degrau mais escuro do teal.
     *
     * O item ativo combina preenchimento com um fio DOURADO à esquerda: cor
     * sozinha não basta, e aqui ela seria a única diferença entre o ativo e o
     * item sob o cursor.
     */
    .fi-sidebar,
    .fi-sidebar-header {
        background-color: var(--nd-teal-700);
        border-color: rgba(242, 237, 230, 0.08);
    }

    .fi-sidebar .fi-sidebar-item-btn,
    .fi-sidebar .fi-sidebar-group-btn,
    .fi-sidebar .fi-sidebar-group-label {
        color: #C6D8E2;
    }

    .fi-sidebar .fi-sidebar-item-btn > .fi-icon {
        color: #8FB2C4;
    }

    .fi-sidebar .fi-sidebar-item-btn:hover,
    .fi-sidebar .fi-sidebar-group-btn:hover {
        background-color: rgba(242, 237, 230, 0.06);
        color: #FFFFFF;
    }

    .fi-sidebar .fi-sidebar-item.fi-active > .fi-sidebar-item-btn {
        background-color: var(--nd-navy);
        box-shadow: inset 3px 0 0 var(--nd-gold);
        color: #FFFFFF;
        font-weight: 600;
    }

    .fi-sidebar .fi-sidebar-item.fi-active > .fi-sidebar-item-btn > .fi-icon {
        color: var(--nd-gold);
    }

    .fi-sidebar .fi-sidebar-item-badge-ctn .fi-badge {
        background-color: var(--nd-gold);
        color: var(--nd-teal-700);
    }

    .fi-sidebar .fi-sidebar-footer,
    .fi-sidebar .fi-sidebar-nav-groups > * + * {
        border-color: rgba(242, 237, 230, 0.08);
    }

    /* Cabeçalho de tabela no navy de marca - o que amarra tabela e barra
       lateral como uma coisa só. */
    .fi-ta-header-cell,
    .fi-ta-header-row {
        background-color: var(--nd-navy);
    }

    .fi-ta-header-cell,
    .fi-ta-header-cell .fi-ta-header-cell-label,
    .fi-ta-header-cell button {
        color: #FFFFFF;
    }

    /* A marca herda a cor de quem a contém: navy no login, clara na barra.
       Fixá-la em navy a deixaria invisível dentro da barra escura. */
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
        color: var(--nd-navy);
    }

    .fi-sidebar-header .fi-logo,
    .fi-sidebar-header .fi-logo span {
        color: #FFFFFF;
    }
</style>
