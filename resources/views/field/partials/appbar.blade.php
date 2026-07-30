{{--
    Barra de topo: marca, contexto do turno, UMA pastilha de sincronização e o
    ajuste de aparência.

    A pastilha abre a fila e tem 48px. Antes tinha ~26px e era o único caminho
    até a fila — num aplicativo declaradamente usado de luva.

    A aparência vive aqui, e não no conteúdo: a área central é do turno, da
    ronda e da ocorrência. Um ajuste no meio dela concorre com o que o
    vigilante está fazendo.
--}}
<header class="appbar">
    {{-- Nos subfluxos a barra de abas some; esta seta é o caminho de volta. --}}
    <template x-if="showBack()">
        <button type="button" class="appbar__back" @click="goBack()" aria-label="Voltar">
            <span aria-hidden="true">‹</span>
        </button>
    </template>

    <div class="appbar__identity">
        <span class="appbar__brand">Notre Guard</span>
        <span class="appbar__context" x-show="shift && post" x-text="contextLine()"></span>
    </div>

    {{-- Uma pastilha por vez, nesta ordem de prioridade: recusado, sem rede,
         enviando, pendente, em dia. --}}
    <template x-if="screen !== 'boot' && screen !== 'login'">
        <button type="button"
                class="chip"
                :class="syncChip().variant"
                @click="openQueue()"
                :aria-label="'Registros no aparelho: ' + syncChip().label">
            <span class="chip__dot" aria-hidden="true"></span>
            <span x-text="syncChip().label"></span>
        </button>
    </template>

    {{-- Disponível também antes do login: escolher o modo noturno é justamente
         o que se quer fazer ao pegar o aparelho no início do turno da noite. --}}
    <button type="button" class="btn btn--ghost btn--icon"
            @click="openThemeSheet()"
            :aria-label="'Aparência: ' + themeLabel() + '. Toque para trocar.'">
        <span aria-hidden="true" x-text="themeMark()"></span>
    </button>
</header>
