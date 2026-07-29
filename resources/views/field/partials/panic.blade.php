{{--
    Confirmação de emergência.

    Não fecha por toque no fundo nem pelo botão voltar sem cancelar
    explicitamente: um acionamento perdido por engano é pior que um
    cancelamento que custou um toque a mais.
--}}
<template x-if="panic.confirming">
    <div class="panic" role="alertdialog" aria-modal="true" aria-labelledby="panic-title"
         x-ref="panic" @keydown.tab="trapFocus($event, $refs.panic)">
        <div class="panic__box">
            <h2 class="panic__title" id="panic-title" tabindex="-1" x-ref="panicTitle">
                Acionar emergência?
            </h2>

            <p class="panic__text">
                A supervisão será avisada agora, com sua localização.
                Use somente em situação real.
            </p>

            <button type="button" class="btn btn--emergency btn--confirm-emergency"
                    :aria-disabled="panic.sending" @click="firePanic()"
                    x-text="panic.sending ? 'Enviando…' : 'SIM, ACIONAR'"></button>

            <button type="button" class="btn btn--secondary" :aria-disabled="panic.sending"
                    @click="cancelPanic()">Cancelar</button>
        </div>
    </div>
</template>
