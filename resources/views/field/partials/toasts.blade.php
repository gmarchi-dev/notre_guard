{{--
    Pilha de toasts, ancorada acima do rodapé.

    Antes o aviso ficava no topo do conteúdo e empurrava o layout - numa tela
    rolada, o vigilante não via. E só cabia um por vez: o seguinte apagava o
    anterior.

    O botão de fechar aparece só no aviso que persiste. Num toast que some
    sozinho em quatro segundos ele pedia uma decisão que ninguém precisa tomar.
--}}
<div class="toasts @if ($floating ?? false) toasts--floating @endif"
     aria-live="polite" aria-atomic="false">
    <template x-for="toast in toasts" :key="toast.id">
        <div class="toast" :class="'toast--' + toast.kind" :role="toast.kind === 'ok' ? 'status' : 'alert'">
            <svg class="icon toast__icon" aria-hidden="true">
                <use :href="toast.kind === 'ok' ? '#i-check-circle' : '#i-alert'"/>
            </svg>

            <div class="toast__body" x-text="toast.text"></div>

            <template x-if="toast.persistent">
                <button type="button" class="btn btn--ghost btn--icon toast__close"
                        @click="dismissToast(toast.id)"
                        :aria-label="'Fechar aviso: ' + toast.text">
                    <svg class="icon" aria-hidden="true"><use href="#i-close"/></svg>
                </button>
            </template>
        </div>
    </template>
</div>
