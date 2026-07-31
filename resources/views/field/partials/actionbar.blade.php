{{--
    Dock: uma ação principal por tela, e a barra de abas embaixo.

    Antes eram até três botões empilhados de largura total mais a emergência -
    cerca de um quarto da altura da tela só de rodapé. Agora navegar e agir não
    disputam mais o mesmo espaço: as ações secundárias vivem no conteúdo, e o
    retorno é a seta do cabeçalho.
--}}
<template x-if="showDock()">
    <div class="dock">
        {{-- Os toasts moram DENTRO do dock: ancorados nele, sobem junto com a
             faixa de ação em vez de cobrir a barra de abas. --}}
        @include('field.partials.toasts')

        {{-- Emergência: único elemento circular e único vermelho preenchido da
             interface. Ancorado ao dock, sobe junto com a faixa de ação. --}}
        <button type="button" class="fab" @click="askPanic()" aria-label="Acionar emergência">
            <svg class="icon" aria-hidden="true"><use href="#i-sos"/></svg>
            <span class="fab__label" aria-hidden="true">SOS</span>
        </button>

        <template x-if="primaryAction()">
            <div class="actionslot">
                <button type="button" class="btn btn--primary"
                        :disabled="primaryAction().disabled"
                        :aria-disabled="busy || syncing"
                        @click="runPrimaryAction()">
                    <span x-text="primaryAction().label"></span>
                    <span class="btn__hint" x-show="primaryAction().hint"
                          x-text="primaryAction().hint"></span>
                </button>
            </div>
        </template>

        <template x-if="showTabBar()">
            @include('field.partials.tabbar')
        </template>
    </div>
</template>
