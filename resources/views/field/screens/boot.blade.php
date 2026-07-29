{{--
    Tela de abertura.

    Existe porque antes não existia: o estado inicial era 'loading' e nenhum
    bloco correspondia a ele, então o aplicativo abria em branco enquanto lia o
    IndexedDB — e ficava preso ali para sempre se a leitura falhasse.
--}}
<template x-if="screen === 'boot'">
    <div class="stack">
        <template x-if="!bootError">
            <div class="stack text-center mt-6">
                <h1 tabindex="-1">Notre Guard</h1>
                <p class="muted">Abrindo o aplicativo…</p>
            </div>
        </template>

        <template x-if="bootError">
            <div class="stack">
                <h1 tabindex="-1">Não foi possível abrir</h1>
                <p class="notice" x-text="bootError"></p>

                <div class="card">
                    <p class="notice">
                        Os registros que ainda não subiram continuam salvos no aparelho.
                        Tente de novo antes de entrar novamente — sair apaga o que está na fila.
                    </p>
                </div>

                <button type="button" class="btn btn--primary" @click="retryBoot()">
                    Tentar de novo
                </button>
                <button type="button" class="btn btn--critical" @click="hardReset()">
                    Entrar de novo
                </button>
            </div>
        </template>
    </div>
</template>
