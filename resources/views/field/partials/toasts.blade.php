{{--
    Pilha de toasts, ancorada acima do rodapé.

    Antes o aviso ficava no topo do conteúdo e empurrava o layout - numa tela
    rolada, o vigilante não via. E só cabia um por vez: o seguinte apagava o
    anterior.
--}}
<div class="toasts" aria-live="polite" aria-atomic="false">
    <template x-for="toast in toasts" :key="toast.id">
        <div class="toast" :class="'toast--' + toast.kind" :role="toast.kind === 'ok' ? 'status' : 'alert'">
            <div class="toast__body" x-text="toast.text"></div>
            <button type="button" class="btn btn--ghost btn--icon" @click="dismissToast(toast.id)"
                    :aria-label="'Fechar aviso: ' + toast.text"><svg class="icon" aria-hidden="true"><use href="#i-close"/></svg></button>
        </div>
    </template>
</div>
