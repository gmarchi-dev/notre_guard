{{--
    Identidade institucional nos painéis Filament.

    Injetado por render hook nos dois painéis, e não como tema Vite do
    Filament: um tema exigiria pipeline de build própria mais `filament:assets`
    no deploy. Aqui é um bloco pequeno, com uma regra por decisão do
    docs/design-system.md - se um dia crescer, aí o tema se justifica.

    A cor primária (navy) não vem daqui: vem do FilamentColor, que gera a
    escala inteira e já pinta botões, badges e navegação. Este arquivo cobre o
    que o FilamentColor não alcança - forma, foco e o dourado de assinatura.
--}}
<style>
    :root {
        /* Os mesmos valores de docs/design-system.md e de
           resources/css/field/tokens.css. Uma paleta, dois sistemas. */
        --nd-navy-900: #013d53;
        --nd-navy-700: #0a5570;
        --nd-gold-500: #cfb276;
        --nd-gold-700: #a88a52;

        /* "6-8px em toda a aplicação". O Filament já usa 4-12px, então aqui a
           mudança é pequena e proposital: alinhar, não apertar. */
        --radius-sm: 0.375rem;
        --radius-md: 0.375rem;
        --radius-lg: 0.5rem;
        --radius-xl: 0.5rem;
    }

    /*
     * Anel de foco dourado, 2px com offset de 2px - a especificação do design
     * system. Vale para todo interativo, não só para os componentes do
     * Filament: teclado é como a portaria opera o dia inteiro.
     */
    .fi-body :focus-visible {
        outline: 2px solid var(--nd-gold-700);
        outline-offset: 2px;
    }

    .dark .fi-body :focus-visible,
    .fi-body.dark :focus-visible {
        outline-color: var(--nd-gold-500);
    }

    /*
     * O traço dourado de 3px no topo é a assinatura do design system: sinaliza
     * "o que chegou ao nível mais alto". Aqui isso é o cabeçalho da página -
     * o que dá ao painel a mesma leitura de selo institucional que a marca
     * carrega, sem gastar área em dourado.
     */
    .fi-topbar {
        border-top: 3px solid var(--nd-gold-500);
    }

    /*
     * Barra lateral em navy.
     *
     * "Navy carrega peso e autoridade: estrutura", diz o design system - e a
     * barra lateral é exatamente a estrutura da tela. Em cinza ela competia
     * com o conteúdo; em navy ela emoldura.
     *
     * A cor entra por variável, não pintando cada elemento: os itens do
     * Filament herdam `currentColor`, então basta inverter o texto no
     * contêiner e tratar os estados.
     */
    .fi-sidebar,
    .fi-sidebar-header {
        background-color: var(--nd-navy-900);
        border-color: var(--nd-navy-700);
    }

    .fi-sidebar .fi-sidebar-item-btn,
    .fi-sidebar .fi-sidebar-group-btn,
    .fi-sidebar .fi-sidebar-group-label {
        /* 8.9:1 contra o navy. O cinza padrão do Filament ficava em 2,4. */
        color: #cfe0e7;
    }

    .fi-sidebar .fi-sidebar-item-btn > .fi-icon {
        color: #9dbcc8;
    }

    .fi-sidebar .fi-sidebar-item-btn:hover,
    .fi-sidebar .fi-sidebar-group-btn:hover {
        background-color: var(--nd-navy-700);
        color: #ffffff;
    }

    /*
     * O item ativo se distingue por preenchimento E por um fio dourado à
     * esquerda - o dourado do design system marcando "onde você está". Cor
     * sozinha não basta, e aqui ela seria a única diferença entre o ativo e
     * o hover.
     */
    .fi-sidebar .fi-sidebar-item.fi-active > .fi-sidebar-item-btn {
        background-color: var(--nd-navy-700);
        box-shadow: inset 3px 0 0 var(--nd-gold-500);
        color: #ffffff;
        font-weight: 600;
    }

    .fi-sidebar .fi-sidebar-item.fi-active > .fi-sidebar-item-btn > .fi-icon {
        color: var(--nd-gold-500);
    }

    /* O contador de pendências precisa continuar legível sobre o navy. */
    .fi-sidebar .fi-sidebar-item-badge-ctn .fi-badge {
        background-color: var(--nd-gold-500);
        color: var(--nd-navy-900);
    }

    /* Separadores e rodapé acompanham, senão sobra um fio cinza claro
       atravessando o navy. */
    .fi-sidebar .fi-sidebar-footer,
    .fi-sidebar .fi-sidebar-nav-groups > * + * {
        border-color: var(--nd-navy-700);
    }

    /* No tema escuro do painel o navy já é próximo do fundo; um tom acima
       mantém a barra distinguível sem virar um bloco claro. */
    .dark .fi-sidebar,
    .dark .fi-sidebar-header {
        background-color: #012a3a;
        border-color: #0a5570;
    }

    /* A marca no topo respira melhor que o nome escrito sozinho. */
    .fi-logo {
        display: flex;
        align-items: center;
        gap: 0.625rem;
    }

    .fi-logo svg {
        display: block;
        height: 1.75rem;
        width: auto;
        /* Herda a cor de quem contém: navy sobre a tela de login, claro dentro
           da barra lateral. Fixar navy aqui deixaria a marca invisível assim
           que a barra virou navy - foi o que quase aconteceu. */
        color: inherit;
    }

    /* Login e outras superfícies claras. */
    .fi-simple-layout .fi-logo,
    .fi-simple-main .fi-logo {
        color: var(--nd-navy-900);
    }

    /* Dentro da barra lateral navy, a marca e o nome do sistema invertem. */
    .fi-sidebar-header .fi-logo,
    .fi-sidebar-header .fi-logo span {
        color: #ffffff;
    }
</style>
