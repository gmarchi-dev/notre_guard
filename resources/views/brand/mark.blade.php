{{--
    Marca do Colégio Notre Dame Campinas.

    Duas variantes, do mesmo arquivo original (img/logo_notre.svg):

      full    brasão + assinatura — login e cabeçalho de painel
      shield  só o brasão — barra do app, espaços estreitos, ícone da PWA

    A cor literal do original (#223f51) foi trocada por `currentColor`. É o que
    permite a mesma marca servir os quatro temas do app de campo e os dois
    painéis sem um arquivo por fundo — e é também o que a harmoniza com o navy
    da interface, em vez de deixar dois azuis quase iguais brigando na tela.

    @param string $variant  'full' | 'shield'
    @param string $class    classes extras
    @param string $label    texto acessível; vazio marca como decorativa
--}}
@php
    $variant = $variant ?? 'full';
    $label = $label ?? 'Colégio Notre Dame Campinas';
    $file = $variant === 'shield' ? 'marca-brasao' : 'marca-completa';
@endphp

<span class="brand {{ $class ?? '' }}"
      @if ($label) role="img" aria-label="{{ $label }}" @else aria-hidden="true" @endif>
    {!! file_get_contents(resource_path("svg/{$file}.svg")) !!}
</span>
