{{--
    Faixas persistentes: o que não pode desaparecer sozinho.

    Diferente do toast, que some. Aqui ficam o estado do acionamento de
    emergência e o aviso de roteiro desatualizado — este último cobre o caso em
    que o bootstrap falha e o vigilante ronda com dados velhos sem saber.
--}}

<template x-if="panic.state">
    <div class="banner" :class="panic.state === 'delivered' ? 'banner--ok' : 'banner--warn'" role="status">
        <div class="banner__body">
            <span x-text="panic.state === 'delivered'
                ? 'Emergência recebida pela supervisão às ' + formatTime(panic.at) + '.'
                : 'Sem rede. O acionamento está salvo no aparelho e sobe assim que houver sinal. Use o rádio.'"></span>
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
