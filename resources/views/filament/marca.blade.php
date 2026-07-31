{{--
    Marca no topo dos painéis: brasão + nome do sistema.

    O brasão identifica a instituição; o nome identifica o sistema. Os dois
    juntos, porque o painel administrativo é uma entre várias aplicações do
    colégio e o vigilante da portaria precisa saber em qual entrou.
--}}
<span class="fi-logo">
    @include('brand.mark', ['variant' => 'shield', 'label' => ''])
    <span>{{ $nome ?? 'Notre Guard' }}</span>
</span>
