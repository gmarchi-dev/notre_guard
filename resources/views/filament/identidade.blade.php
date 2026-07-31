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
        color: var(--nd-navy-900);
    }

    .dark .fi-logo svg {
        color: #ffffff;
    }
</style>
