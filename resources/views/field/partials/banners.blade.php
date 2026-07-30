{{--
    Faixas persistentes: o que não pode desaparecer sozinho.

    Diferente do toast, que some. Aqui ficam o estado do acionamento de
    emergência e o aviso de roteiro desatualizado - este último cobre o caso em
    que o bootstrap falha e o vigilante ronda com dados velhos sem saber.
--}}

{{--
    Três estados, e a distinção entre eles é o ponto: "o servidor gravou" não é
    "alguém está indo". Só o reconhecimento humano fecha o ciclo.
--}}
<template x-if="panic.state">
    <div class="banner" :class="panic.state === 'acknowledged' ? 'banner--ok' : 'banner--warn'" role="status">
        <div class="banner__body">
            <template x-if="panic.state === 'acknowledged'">
                <span>
                    <strong x-text="panicAcknowledgedLabel"></strong>
                    Mantenha o rádio ligado.
                </span>
            </template>
            <template x-if="panic.state === 'delivered'">
                <span x-text="'Acionamento recebido às ' + formatTime(panic.at) + '. Aguardando a supervisão reconhecer. Use o rádio.'"></span>
            </template>
            <template x-if="panic.state === 'queued'">
                <span>Sem rede. O acionamento está salvo no aparelho e sobe assim que houver sinal. Use o rádio.</span>
            </template>
        </div>
        <button type="button" class="btn btn--ghost btn--icon" @click="dismissPanicState()"
                aria-label="Fechar aviso de emergência">&times;</button>
    </div>
</template>

<template x-if="dataStale">
    <div class="banner banner--warn" role="status">
        <div class="banner__body">
            <span x-text="'Roteiro carregado ' + dataAgeLabel() + '. Sem contato com o servidor desde então.'"></span>
        </div>
        <button type="button" class="btn btn--ghost btn--icon" @click="refreshData({ force: true })"
                aria-label="Tentar atualizar os dados">&#8635;</button>
    </div>
</template>
