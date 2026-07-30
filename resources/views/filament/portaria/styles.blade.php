{{--
    Realce da chave atrasada no quadro.

    Vai inline num render hook, e não como tema Vite do Filament, de propósito:
    um tema exigiria pipeline de build própria e `filament:assets` no deploy -
    custo desproporcional para uma dúzia de linhas. Se um dia a portaria pedir
    identidade visual própria, aí sim vale o tema.

    O atraso já aparecia num badge vermelho dentro da linha. O que faltava era
    a linha inteira se distinguir: no balcão ninguém lê coluna por coluna.
--}}
<style>
    .ng-row-overdue > td {
        background-color: rgb(254 242 242);
        box-shadow: inset 3px 0 0 rgb(220 38 38);
    }

    .dark .ng-row-overdue > td {
        background-color: rgb(69 10 10 / 0.35);
        box-shadow: inset 3px 0 0 rgb(248 113 113);
    }
</style>
